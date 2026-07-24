<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuickViewController extends Controller
{
    public function show(Request $request, $parentSlug, $attributeValueSlug = null)
    {
        try {
            $product = Product::where('slug', $parentSlug)
                ->where('product_status', 1)
                ->with([
                    'category:id,title,slug',
                    'images:id,product_id,image_path,sort_order',
                    'inventories' => function ($q) {
                        $q->orderBy('mrp', 'asc');
                    },
                    'productAttributesValues.attributeValue.attribute:id,title,slug',
                ])
                ->first();

            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
            }

            $inventory = $product->inventories->first();
            $images = $product->images
                ->sortBy('sort_order')
                ->pluck('image_path')
                ->map(fn($path) => asset('storage/images/product/small/' . $path))
                ->values();
            $attributes = $product->productAttributesValues
                ->groupBy(fn($pav) => $pav->attributeValue?->attribute?->title)
                ->filter(fn($group, $title) => (bool) $title)
                ->map(fn($group, $title) => [
                    'title' => $title,
                    'values' => $group->pluck('attributeValue.name')->filter()->unique()->values(),
                ])
                ->values();
            return response()->json([
                'success' => true,
                'message' => 'Product fetched successfully.',
                'data' => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'category' => [
                        'title' => optional($product->category)->title,
                        'slug' => optional($product->category)->slug,
                    ],
                    'images' => $images,
                    'short_description' => $product->product_short_description,
                    'mrp' => $inventory?->mrp,
                    'offer_rate' => $inventory?->offer_rate,
                    'sku' => $inventory?->sku,
                    'stock_quantity' => $inventory?->stock_quantity,
                    'in_stock' => $inventory === null || $inventory->stock_quantity === null || $inventory->stock_quantity > 0,
                    'attributes' => $attributes,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Quick view fetch failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not load product.'], 500);
        }
    }
}
