<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{

    private const SESSION_KEY = 'cart';

    /**
     * How long a guest cart survives with no activity, when identified
     * via X-Cart-Token (Cache-backed) rather than the PHP session.
     */
    private const GUEST_CART_TTL_DAYS = 30;

    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'nullable|integer|min:1',
        ]);
        if ($validator->fails()) {
            return $this->invalidRequestResponse($validator->errors()->first());
        }
        $productId = (int) $request->input('product_id');
        $quantity  = (int) $request->input('quantity', 1);
        $product = Product::where('id', $productId)->where('product_status', 1)->first();
        if (!$product) {
            return $this->notFoundResponse('Product not found or is unavailable.');
        }
        $inventory = $this->resolveCheapestInventory($productId);
        if (!$inventory) {
            return $this->invalidRequestResponse('This product currently has no purchasable inventory.');
        }
        if ($inventory->stock_quantity !== null && $inventory->stock_quantity < $quantity) {
            return $this->invalidRequestResponse('Only ' . $inventory->stock_quantity . ' unit(s) left in stock.');
        }
        try {
            $user = $request->user();
            if ($user) {
                $this->addToDbCart($user->id, $productId, $inventory->id, $quantity);
            } else {
                $this->addToSessionCart($productId, $quantity);
            }
            return response()->json([
                'success' => true,
                'message' => 'Product added to cart.',
                'data' => $this->buildCartResponse($request),
            ]);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e, 'Could not add the product to your cart.');
        }
    }

    public function cartList(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Cart fetched successfully.',
                'data' => $this->buildCartResponse($request),
            ]);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e, 'Could not load your cart.');
        }
    }

    public function updateCartItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return $this->invalidRequestResponse($validator->errors()->first());
        }
        $productId = (int) $id;
        $quantity = (int) $request->input('quantity');
        try {
            $user = $request->user();
            if ($user) {
                $cartItem = Cart::where('user_id', $user->id)->where('product_id', $productId)->first();
                if (!$cartItem) {
                    return $this->notFoundResponse('This product is not in your cart.');
                }
                $inventory = $cartItem->inventory_id
                    ? Inventory::find($cartItem->inventory_id)
                    : $this->resolveCheapestInventory($productId);
                if ($inventory && $inventory->stock_quantity !== null && $inventory->stock_quantity < $quantity) {
                    return $this->invalidRequestResponse('Only ' . $inventory->stock_quantity . ' unit(s) left in stock.');
                }
                $cartItem->quantity = $quantity;
                $cartItem->save();
            } else {
                $cart = $this->getGuestCart($request);
                if (!isset($cart[$productId])) {
                    return $this->notFoundResponse('This product is not in your cart.');
                }
                $inventory = $this->resolveCheapestInventory($productId);
                if ($inventory && $inventory->stock_quantity !== null && $inventory->stock_quantity < $quantity) {
                    return $this->invalidRequestResponse('Only ' . $inventory->stock_quantity . ' unit(s) left in stock.');
                }
                $cart[$productId]['quantity'] = $quantity;
                $this->saveGuestCart($request, $cart);
            }
            return response()->json([
                'success' => true,
                'message' => 'Cart updated.',
                'data' => $this->buildCartResponse($request),
            ]);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e, 'Could not update your cart.');
        }
    }

    public function removeFromCart(Request $request, $id)
    {
        $productId = (int) $id;
        try {
            $user = $request->user();
            if ($user) {
                Cart::where('user_id', $user->id)->where('product_id', $productId)->delete();
            } else {
                $cart = $this->getGuestCart($request);
                unset($cart[$productId]);
                $this->saveGuestCart($request, $cart);
            }
            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart.',
                'data' => $this->buildCartResponse($request),
            ]);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e, 'Could not remove the item from your cart.');
        }
    }

    public function clearCart(Request $request)
    {
        try {
            $user = $request->user();
            if ($user) {
                Cart::where('user_id', $user->id)->delete();
            } else {
                $this->clearGuestCart($request);
            }
            return response()->json([
                'success' => true,
                'message' => 'Cart cleared.',
                'data' => $this->buildCartResponse($request),
            ]);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e, 'Could not clear your cart.');
        }
    }

    public function mergeSessionCartIntoUser(Request $request, int $userId): void
    {
        $sessionCart = $this->getGuestCart($request);
        if (empty($sessionCart)) {
            return;
        }
        DB::transaction(function () use ($sessionCart, $userId) {
            foreach ($sessionCart as $productId => $item) {
                $quantity = (int) ($item['quantity'] ?? 1);
                if ($quantity < 1) {
                    continue;
                }
                $product = Product::where('id', $productId)->where('product_status', 1)->first();
                if (!$product) {
                    continue;
                }
                $inventory = $this->resolveCheapestInventory($productId);
                $existing = Cart::where('user_id', $userId)->where('product_id', $productId)->first();
                if ($existing) {
                    $existing->quantity += $quantity;
                    if ($inventory && $inventory->stock_quantity !== null) {
                        $existing->quantity = min($existing->quantity, $inventory->stock_quantity);
                    }
                    $existing->save();
                } else {
                    Cart::create([
                        'user_id' => $userId,
                        'product_id' => $productId,
                        'inventory_id' => $inventory?->id,
                        'quantity' => $inventory && $inventory->stock_quantity !== null
                            ? min($quantity, $inventory->stock_quantity)
                            : $quantity,
                    ]);
                }
            }
        });
        $this->clearGuestCart($request);
    }

    /* =========================================================================
     |  INTERNAL HELPERS
     * ========================================================================= */
    private function addToDbCart(int $userId, int $productId, int $inventoryId, int $quantity): void
    {
        $cartItem = Cart::where('customer_id', $userId)->where('product_id', $productId)->first();
        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->inventory_id = $inventoryId;
            $cartItem->save();
        } else {
            Cart::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'inventory_id' => $inventoryId,
                'quantity' => $quantity,
            ]);
        }
    }

    private function addToSessionCart(int $productId, int $quantity): void
    {
        $cart = $this->getGuestCart(request());
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = ['quantity' => $quantity];
        }
        $this->saveGuestCart(request(), $cart);
    }

    /* =========================================================================
     |  GUEST CART STORAGE — X-Cart-Token (Cache) with session() fallback
     |
     |  Cross-origin PHP session cookies are unreliable across different
     |  domains (e.g. Vercel frontend + this API on its own domain) — the
     |  frontend sends a stable "X-Cart-Token" header (generated once,
     |  stored in localStorage) on every cart request. When present, we
     |  use it as a Cache key instead of the session — completely
     |  sidestepping cookie issues. If the header is absent, we fall back
     |  to the previous session-based behavior so nothing breaks.
     * ========================================================================= */
    private function guestCartToken(Request $request): ?string
    {
        $token = $request->header('X-Cart-Token');
        return $token ? trim($token) : null;
    }

    private function guestCartCacheKey(string $token): string
    {
        return "cart:{$token}";
    }

    private function getGuestCart(Request $request): array
    {
        $token = $this->guestCartToken($request);
        if ($token) {
            return Cache::get($this->guestCartCacheKey($token), []);
        }
        return session(self::SESSION_KEY, []);
    }

    private function saveGuestCart(Request $request, array $cart): void
    {
        $token = $this->guestCartToken($request);
        if ($token) {
            Cache::put($this->guestCartCacheKey($token), $cart, now()->addDays(self::GUEST_CART_TTL_DAYS));
            return;
        }
        session([self::SESSION_KEY => $cart]);
    }

    private function clearGuestCart(Request $request): void
    {
        $token = $this->guestCartToken($request);
        if ($token) {
            Cache::forget($this->guestCartCacheKey($token));
            return;
        }
        session()->forget(self::SESSION_KEY);
    }

    /**
     * "inventory cart check min mrp from product id" — always the cheapest
     * inventory row for a given product.
     */
    private function resolveCheapestInventory(int $productId): ?Inventory
    {
        return Inventory::where('product_id', $productId)
            ->orderBy('mrp', 'asc')
            ->first();
    }

    private function buildCartResponse(Request $request): array
    {
        $user = $request->user();
        if ($user) {
            $items = Cart::where('user_id', $user->id)
                ->with([
                    'product:id,title,slug,category_id',
                    'product.category:id,title,slug',
                    'product.firstSortedImage:id,product_id,image_path',
                    'inventory:id,product_id,mrp,offer_rate,stock_quantity,sku',
                ])
                ->get()
                ->map(fn($item) => $this->formatCartItem(
                    $item->product_id,
                    $item->quantity,
                    $item->product,
                    $item->inventory
                ))
                ->filter()
                ->values();
        } else {
            $sessionCart = $this->getGuestCart($request);
            $productIds = array_keys($sessionCart);
            $products = Product::whereIn('id', $productIds)
                ->where('product_status', 1)
                ->with([
                    'category:id,title,slug',
                    'firstSortedImage:id,product_id,image_path',
                ])
                ->get()
                ->keyBy('id');
            $items = collect($sessionCart)
                ->map(function ($item, $productId) use ($products) {
                    $product = $products->get($productId);
                    if (!$product) {
                        return null;
                    }
                    $inventory = $this->resolveCheapestInventory((int) $productId);
                    return $this->formatCartItem((int) $productId, (int) $item['quantity'], $product, $inventory);
                })
                ->filter()
                ->values();
        }
        $subtotal = $items->sum(fn($item) => $item['line_total']);
        return [
            'items' => $items,
            'item_count' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'subtotal' => $subtotal,
            'is_guest_cart' => $user === null,
        ];
    }

    private function formatCartItem(int $productId, int $quantity, $product, $inventory): ?array
    {
        if (!$product) {
            return null;
        }
        $price = $inventory?->offer_rate ?? $inventory?->mrp;
        $lineTotal = $price !== null ? $price * $quantity : null;
        return [
            'product_id' => $productId,
            'title' => $product->title,
            'slug' => $product->slug,
            'category' => [
                'title' => optional($product->category)->title,
                'slug' => optional($product->category)->slug,
            ],
            'image' => $product->firstSortedImage
                ? $product->firstSortedImage->getSmallImages()
                : null,
            'mrp' => $inventory?->mrp,
            'offer_rate' => $inventory?->offer_rate,
            'sku' => $inventory?->sku,
            'in_stock' => $inventory === null || $inventory->stock_quantity === null || $inventory->stock_quantity > 0,
            'available_stock' => $inventory?->stock_quantity,
            'quantity' => $quantity,
            'line_total' => $lineTotal,
        ];
    }

    /* =========================================================================
     |  STANDARDIZED ERROR RESPONSES
     * ========================================================================= */
    private function notFoundResponse(string $message)
    {
        return response()->json(['success' => false, 'error_code' => 'NOT_FOUND', 'message' => $message], 404);
    }
    private function invalidRequestResponse(string $message)
    {
        return response()->json(['success' => false, 'error_code' => 'INVALID_REQUEST', 'message' => $message], 400);
    }

    private function serverErrorResponse(\Throwable $e, string $userMessage)
    {
        Log::error($userMessage . ' | ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        $payload = ['success' => false, 'error_code' => 'SERVER_ERROR', 'message' => $userMessage];
        if (config('app.debug')) {
            $payload['debug'] = ['exception' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()];
        }
        return response()->json($payload, 500);
    }
}
