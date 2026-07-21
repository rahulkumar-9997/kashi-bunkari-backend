<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
        ]);
        if ($validator->fails()) {
            return $this->invalidRequestResponse($validator->errors()->first());
        }

        $productId = (int) $request->input('product_id');
        $customerId = $request->user()->id;

        $product = Product::where('id', $productId)->where('product_status', 1)->first();
        if (!$product) {
            return $this->notFoundResponse('Product not found or is unavailable.');
        }

        try {
            Wishlist::firstOrCreate([
                'customer_id' => $customerId,
                'product_id' => $productId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist.',
                'data' => $this->buildWishlistResponse($customerId),
            ]);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e, 'Could not add the product to your wishlist.');
        }
    }

   
    public function list(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Wishlist fetched successfully.',
                'data' => $this->buildWishlistResponse($request->user()->id),
            ]);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e, 'Could not load your wishlist.');
        }
    }

    
    public function remove(Request $request, $id)
    {
        $productId = (int) $id;
        $customerId = $request->user()->id;

        try {
            Wishlist::where('customer_id', $customerId)->where('product_id', $productId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist.',
                'data' => $this->buildWishlistResponse($customerId),
            ]);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e, 'Could not remove the product from your wishlist.');
        }
    }

    
    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
        ]);
        if ($validator->fails()) {
            return $this->invalidRequestResponse($validator->errors()->first());
        }

        $productId = (int) $request->input('product_id');
        $customerId = $request->user()->id;

        try {
            $existing = Wishlist::where('customer_id', $customerId)->where('product_id', $productId)->first();

            if ($existing) {
                $existing->delete();
                $wishlisted = false;
                $message = 'Product removed from wishlist.';
            } else {
                $product = Product::where('id', $productId)->where('product_status', 1)->first();
                if (!$product) {
                    return $this->notFoundResponse('Product not found or is unavailable.');
                }
                Wishlist::create(['customer_id' => $customerId, 'product_id' => $productId]);
                $wishlisted = true;
                $message = 'Product added to wishlist.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => array_merge(
                    ['wishlisted' => $wishlisted],
                    $this->buildWishlistResponse($customerId),
                ),
            ]);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e, 'Could not update your wishlist.');
        }
    }
    
    private function buildWishlistResponse(int $customerId): array
    {
        $items = Wishlist::where('customer_id', $customerId)
            ->with([
                'product:id,title,slug,category_id,product_status',
                'product.category:id,title,slug',
                'product.firstSortedImage:id,product_id,image_path',
                'product.inventories' => function ($query) {
                    $query->select('id', 'product_id', 'mrp', 'offer_rate', 'purchase_rate', 'sku', 'stock_quantity')
                        ->orderBy('mrp', 'asc');
                },
                'product.productAttributesValues.attributeValue:id,slug',
            ])
            ->latest()
            ->get()
            ->map(fn($wishlistItem) => $this->formatWishlistItem($wishlistItem->product))
            ->filter() // drop any items whose product vanished or was deactivated
            ->values();

        return [
            'items' => $items,
            'item_count' => $items->count(),
        ];
    }

    private function formatWishlistItem($product): ?array
    {
        if (!$product || $product->product_status != 1) {
            return null;
        }

        $inventory = $product->inventories->first();
        $attributeSlug = optional(
            optional($product->productAttributesValues->first())->attributeValue
        )->slug;

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'mrp' => $inventory->mrp ?? null,
            'offer_rate' => $inventory->offer_rate ?? null,
            'sku' => $inventory->sku ?? null,
            'attribute_value' => $attributeSlug,
            'category' => [
                'title' => $product->category->title ?? null,
                'slug'  => $product->category->slug ?? null,
            ],
            'image' => $product->firstSortedImage ? $product->firstSortedImage->getSmallImages() : null,
            'in_stock' => $inventory === null || ($inventory->stock_quantity ?? null) === null || $inventory->stock_quantity > 0,
        ];
    }
	
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