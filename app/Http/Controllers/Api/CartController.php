<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id'  => 'required|exists:products,id',
                'quantity'    => 'required|integer|min:1',
                'product_mrp' => 'nullable|numeric|min:0',
            ], [
                'product_id.required' => 'Product ID is required.',
                'product_id.exists'   => 'Selected product does not exist.',
                'quantity.required'   => 'Quantity is required.',
                'quantity.min'        => 'Quantity must be at least 1.',
            ]);
            $productId = $validated['product_id'];
            $quantity  = $validated['quantity'];
            $mrp       = $validated['product_mrp'] ?? null;
            $product = Product::where('id', $productId)
                ->where('product_status', 1)
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is currently unavailable.'
                ], 404);
            }

            $inventoryQuery = Inventory::where('product_id', $productId);
            if ($mrp) {
                $inventoryQuery->where('mrp', $mrp);
            }
            $inventory = $inventoryQuery->first();
            if (!$inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product inventory not found.'
                ], 404);
            }
            $customerId = Auth::guard('sanctum')->check()
                ? Auth::guard('sanctum')->id()
                : null;

            $sessionId = $this->getOrCreateSessionId($request);

            DB::beginTransaction();
            try {
                $cart = Cart::where('product_id', $productId);                
                if ($customerId) {
                    $cart = $cart->where('customer_id', $customerId);
                } else {
                    $cart = $cart->where('session_id', $sessionId);
                }                
                $cart = $cart->first();

                $existingQuantity = $cart ? $cart->quantity : 0;
                $newQuantity      = $existingQuantity + $quantity;
                if ($inventory->stock_quantity < $newQuantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Only {$inventory->stock_quantity} items available in stock."
                    ], 400);
                }
                if ($cart) {
                    $cart->update(['quantity' => $newQuantity]);
                } else {
                    $cartData = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                    ];
                    
                    if ($customerId) {
                        $cartData['customer_id'] = $customerId;
                        $cartData['session_id'] = null;
                    } else {
                        $cartData['customer_id'] = null;
                        $cartData['session_id'] = $sessionId;
                    }
                    
                    $cart = Cart::create($cartData);
                }
                DB::commit();
                /* ── Build response with cookie */
                $responseData = [
                    'success'    => true,
                    'message'    => 'Product added to cart successfully.',
                    'data'       => $this->getCartDetailsWithInventory($customerId, $sessionId),
                    'cart_count' => $this->getCartItemCount($customerId, $sessionId),
                ];
                if (!$customerId) {
                    $responseData['session_id'] = $sessionId;
                }

                $response = response()->json($responseData, 200);

                if (!$customerId && !$request->cookie('cart_session_id')) {
                    $response->cookie(
                        'cart_session_id',
                        $sessionId,
                        60 * 24 * 30, // 30 days
                        '/',
                        null,
                        false, // Set to true in production with HTTPS
                        true // HttpOnly
                    );
                }

                return $response;
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Cart Transaction Error:', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong while adding to cart.'
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Cart General Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Server error. Please try again later.'
            ], 500);
        }
    }

    private function getOrCreateSessionId(Request $request): string
    {
       
        $sessionId = $request->header('X-Session-ID');
        if ($sessionId) {
            return $sessionId;
        }
        $sessionId = $request->cookie('cart_session_id');
        if ($sessionId) {
            return $sessionId;
        }
        $sessionId = Session::get('cart_session_id');
        if ($sessionId) {
            return $sessionId;
        }
        return $this->generatePersistentSessionId();
    }

    /**
     * Generate a new unique session ID and persist it in the Laravel session.
     */
    private function generatePersistentSessionId(): string
    {
        $sessionId = md5(uniqid('cart_', true) . time());
        Session::put('cart_session_id', $sessionId);
        Session::save();
        return $sessionId;
    }

    /**
     * Get cart list with inventory details
     */
    public function cartList(Request $request)
    {
        try {
            $customerId = null;
            if (Auth::guard('sanctum')->check()) {
                $customerId = Auth::guard('sanctum')->id();
            }
            $sessionId = $request->cookie('cart_session_id'); 
            Log::info("Cart Products", [
                'session_id' => $sessionId
            ]);

            $cartData = $this->getCartDetailsWithInventory($customerId, $sessionId);
            $cartCount = $this->getCartItemCount($customerId, $sessionId);
            return response()->json([
                'success' => true,
                'data' => $cartData,
                'cart_count' => $cartCount
            ]);
        } catch (\Exception $e) {
            Log::error('Cart List Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update cart item quantity with inventory check
     */
    public function updateCartItem(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:0|max:999'
            ]);            
            $quantity = $validated['quantity'];
            $customerId = null;
            if (Auth::guard('sanctum')->check()) {
                $customerId = Auth::guard('sanctum')->id();
            }
            $sessionId = $request->cookie('cart_session_id');            
            Log::info("Cart Products", [
                'session_id' => $sessionId
            ]);            
            DB::beginTransaction();            
            $cartItem = Cart::where('id', $id);            
            if ($customerId) {
                $cartItem = $cartItem->where('customer_id', $customerId);
            } else {
                $cartItem = $cartItem->where('session_id', $sessionId);
            }            
            $cartItem = $cartItem->first();            
            if (!$cartItem) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cart item not found'
                ], 404);
            }            
            if ($quantity <= 0) {
                $cartItem->delete();
                DB::commit();
                $cartData = $this->getCartDetailsWithInventory($customerId, $sessionId);
                $cartCount = $this->getCartItemCount($customerId, $sessionId);
                return response()->json([
                    'success' => true,
                    'message' => 'Item removed from cart',
                    'data' => $cartData,
                    'cart_count' => $cartCount
                ]);
            }
            
            $inventory = Inventory::where('product_id', $cartItem->product_id)
                ->orderBy('mrp')
                ->first();
            
            if (!$inventory || $inventory->stock_quantity < $quantity) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Not enough stock available. Only ' . ($inventory->stock_quantity ?? 0) . ' items in stock.'
                ], 400);
            }
            
            $cartItem->quantity = $quantity;
            $cartItem->save();
            
            DB::commit();
            
            $cartData = $this->getCartDetailsWithInventory($customerId, $sessionId);
            $cartCount = $this->getCartItemCount($customerId, $sessionId);
            
            return response()->json([
                'success' => true,
                'message' => 'Cart updated successfully',
                'data' => $cartData,
                'cart_count' => $cartCount
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'success' => false,
                'message' => $e->errors()['quantity'][0] ?? 'Validation failed',
                'error' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    /**
     * Remove item from cart
     */
    public function removeFromCart(Request $request, $id)
    {
        try {
            $customerId = null;
            if (Auth::guard('sanctum')->check()) {
                $customerId = Auth::guard('sanctum')->id();
            }

            $sessionId = $request->cookie('cart_session_id');            
            Log::info("removeFromCart", [
                'cart_id' => $id,
                'customer_id' => $customerId,
                'session_id' => $sessionId
            ]);
            DB::beginTransaction();
            try {
                $cartItem = Cart::where('id', $id);            
                    if ($customerId) {
                        $cartItem = $cartItem->where('customer_id', $customerId);
                    } else {
                        $cartItem = $cartItem->where('session_id', $sessionId);
                    }            
                    $cartItem = $cartItem->first(); 

                if (!$cartItem) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Cart item not found'
                    ], 404);
                }

                $cartItem->delete();
                DB::commit();
                $cartData = $this->getCartDetailsWithInventory($customerId, $sessionId);
                $cartCount = $this->getCartItemCount($customerId, $sessionId);

                return response()->json([
                    'success' => true,
                    'message' => 'Item removed from cart successfully',
                    'data' => $cartData,
                    'cart_count' => $cartCount
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Remove Cart Error (Transaction): ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove item from cart',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Remove Cart Error (General): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart(Request $request)
    {
        try {
            $customerId = null;
            if (Auth::guard('sanctum')->check()) {
                $customerId = Auth::guard('sanctum')->id();
            }

            $sessionId = Session::getId();

            DB::beginTransaction();

            try {
                if ($customerId) {
                    Cart::where('customer_id', $customerId)->delete();
                } else {
                    Cart::where('session_id', $sessionId)->delete();
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Cart cleared successfully',
                    'data' => [
                        'items' => [],
                        'total_items' => 0,
                        'total_price' => 0,
                        'item_count' => 0,
                        'subtotal' => 0
                    ],
                    'cart_count' => 0
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Clear Cart Error (Transaction): ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to clear cart',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Clear Cart Error (General): ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Merge guest cart with user cart after login
     */
    public function mergeCartAfterLogin(Request $request)
    {
        try {
            $customerId = Auth::guard('sanctum')->id();

            if (!$customerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $sessionId = Session::getId();

            DB::beginTransaction();

            try {
                $guestCartItems = Cart::where('session_id', $sessionId)
                    ->whereNull('customer_id')
                    ->get();

                if ($guestCartItems->isEmpty()) {
                    DB::commit();
                    $cartData = $this->getCartDetailsWithInventory($customerId, null);
                    $cartCount = $this->getCartItemCount($customerId, null);

                    return response()->json([
                        'success' => true,
                        'message' => 'No guest cart items to merge',
                        'data' => $cartData,
                        'cart_count' => $cartCount
                    ]);
                }

                $mergedCount = 0;
                $newCount = 0;

                foreach ($guestCartItems as $guestItem) {
                    // Check if product exists and is active
                    $product = Product::where('id', $guestItem->product_id)
                        ->where('product_status', 1)
                        ->first();

                    if (!$product) {
                        $guestItem->delete();
                        continue;
                    }

                    // Check inventory stock
                    $inventory = Inventory::where('product_id', $guestItem->product_id)
                        ->orderBy('mrp')
                        ->first();

                    // Check if product already exists in user's cart
                    $userCartItem = Cart::where('customer_id', $customerId)
                        ->where('product_id', $guestItem->product_id)
                        ->first();

                    if ($userCartItem) {
                        $newQuantity = $userCartItem->quantity + $guestItem->quantity;

                        // Check stock availability
                        if ($inventory && $inventory->stock_quantity >= $newQuantity) {
                            $userCartItem->quantity = $newQuantity;
                            $userCartItem->save();
                            $guestItem->delete();
                            $mergedCount++;
                        } else {
                            // If not enough stock, keep existing quantity
                            $guestItem->delete();
                        }
                    } else {
                        // Check stock availability
                        if ($inventory && $inventory->stock_quantity >= $guestItem->quantity) {
                            $guestItem->customer_id = $customerId;
                            $guestItem->session_id = null;
                            $guestItem->save();
                            $newCount++;
                        } else {
                            $guestItem->delete();
                        }
                    }
                }

                DB::commit();

                $cartData = $this->getCartDetailsWithInventory($customerId, null);
                $cartCount = $this->getCartItemCount($customerId, null);

                return response()->json([
                    'success' => true,
                    'message' => 'Cart merged successfully',
                    'data' => $cartData,
                    'cart_count' => $cartCount,
                    'meta' => [
                        'merged_items' => $mergedCount,
                        'new_items' => $newCount
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Merge Cart Error (Transaction): ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to merge cart',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Merge Cart Error (General): ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get complete cart details with inventory and product images
     */
    private function getCartDetailsWithInventory($customerId, $sessionId)
    {
        
        $cartItems = Cart::where(function ($query) use ($customerId, $sessionId) {
            if ($customerId) {
                $query->where('customer_id', $customerId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })
        ->with([
            'product' => function ($query) {
                $query->where('product_status', 1)
                    ->select('id', 'title', 'slug', 'category_id');
            },
            'product.category:id,title,slug',
            'product.firstSortedImage:id,product_id,image_path',
            'product.images:id,product_id,image_path,sort_order',
            'product.inventories' => function ($query) {
                $query->select('id', 'product_id', 'mrp', 'offer_rate', 'purchase_rate', 'sku', 'stock_quantity')
                    ->orderBy('mrp', 'asc');
            },
            'product.productAttributesValues.attributeValue:id,slug'
        ])
        ->get();
        Log::info("Cart Products:\n" . json_encode($cartItems, JSON_PRETTY_PRINT));
        if ($cartItems->isEmpty()) {
            return [
                'items' => [],
                'total_items' => 0,
                'total_price' => 0,
                'item_count' => 0,
                'subtotal' => 0,
                'delivery_charge' => 0,
                'grand_total' => 0
            ];
        }

        $total = 0;
        $totalItems = 0;
        $formattedItems = [];
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if (!$product) {
                continue;
            }
            $inventory = $product->inventories->first();
            $price = $inventory->offer_rate ?? $inventory->mrp ?? $product->product_sale_price ?? $product->product_price ?? 0;
            $itemTotal = $price * $cartItem->quantity;
            $total += $itemTotal;
            $totalItems += $cartItem->quantity;
            $attributeSlug = optional(
                $product->productAttributesValues->first()
            )->attributeValue->slug ?? null;
            $attributeValue = optional(
                $product->productAttributesValues->first()
            )->attributeValue->value ?? null;
            $formattedItems[] = [
                'cart_id' => $cartItem->id,
                'product_id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'quantity' => $cartItem->quantity,
                'mrp' => $inventory->mrp ?? $product->product_price ?? null,
                'offer_rate' => $inventory->offer_rate ?? null,
                'purchase_rate' => $inventory->purchase_rate ?? null,
                'per_unit_price' => round($price, 2),
                'total_price' => round($itemTotal, 2),
                'attribute_value_slug' => $attributeSlug,
                'category' => $product->category->title ?? null,
                'category_slug' => $product->category->slug ?? null,                
                'image' => $product->firstSortedImage ? $product->firstSortedImage->getSmallImages() : null,
            ];
        }

        return [
            'items' => $formattedItems,
            'total_price' => round($total, 2),
            'subtotal' => round($total, 2),
            'item_count' => count($formattedItems),
            'grand_total' => round($total, 2),
        ];
    }

    private function getCartItemCount($customerId, $sessionId)
    {
        if ($customerId) {
            return Cart::where('customer_id', $customerId)->count();
        } else {
            return Cart::where('session_id', $sessionId)->count();
        }
    }
}
