<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute_values;
use App\Models\Attribute;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\Inventory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function searchSuggestions(Request $request)
    {
        $query = trim($request->input('query', ''));
        if (empty(trim($query))) {
            return response()->json(['suggestions' => []]);
        }
        Log::error('suggestion log: ' . $query);           
        $searchTerms = explode(' ', $query);
        /* Search products */
        $booleanQuery = '+' . implode(' +', $searchTerms);
        $products = Product::whereRaw("MATCH(title) AGAINST(? IN BOOLEAN MODE)", [$booleanQuery])
            ->orWhere(function ($query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $query->orWhere('title', 'like', '%' . $term . '%');
                }
                foreach ($searchTerms as $term) {
                    $query->orWhereRaw("SOUNDEX(?) = SOUNDEX(SUBSTRING_INDEX(title, ' ', 1))", [$term]);
                }
            })
            ->with([
                'firstImage',
                'category',
                'ProductAttributesValues' => function ($query) {
                    $query->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
                    ->with(['attributeValue:id,slug'])
                    ->orderBy('id');
                }
            ])
            ->leftJoin('inventories', function ($join) {
                $join->on('products.id', '=', 'inventories.product_id')
                ->whereRaw('inventories.mrp = (SELECT MIN(mrp) FROM inventories WHERE product_id = products.id)');
            })
            ->select('products.*', 'inventories.mrp', 'inventories.offer_rate', 'inventories.purchase_rate', 'inventories.sku')
            ->limit(5)
            ->get(['id', 'title', 'slug']); 

        /* Search categories */
        $categories = Category::where(function ($query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $query->where('title', 'like', '%' . $term . '%');
            }
        })
        ->limit(5)
        ->get(['id', 'title']);

        /* Search attribute values */
        $attributeValues = Attribute_values::where(function ($query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $query->where('name', 'like', '%' . $term . '%');
            }
        })
        ->with('attribute')
        ->limit(5)
        ->get(['id', 'name as title', 'attributes_id']);
        $suggestions = collect();
        
        /* Add attribute values (as suggestions) */
        $suggestions = $suggestions->merge($attributeValues->map(function ($value) {
            return [
                'type' => 'suggestion',
                'title' => ucwords(strtolower($value->title)),
                'image' => null,
            ];
        }));
        
        /* Add categories (as suggestions) */
        $suggestions = $suggestions->merge($categories->map(function ($category) {
            return [
                'type' => 'suggestion',
                'title' =>  ucwords(strtolower($category->title)),
                'image' => null,
            ];
        }));
        $attributes_value = null;
        $suggestions = $suggestions->merge($products->map(function ($product) {
            $image = $product->firstImage;
            if($product->ProductAttributesValues->isNotEmpty()){
                $attributes_value = $product->ProductAttributesValues->first()->attributeValue->slug;
            }
            if ($product->offer_rate)
            {
                $offer_rate = 'Rs. ' . $product->offer_rate;
            }
            else
            {
                $offer_rate = null;
            }
            return [
                'type' => 'product',
                'title' => ucwords(strtolower($product->title)),
                'slug' => $product->slug,
                'attributes_value_slug' => $attributes_value,
                'category' => $product->category->title,
                'offer_rate' => $offer_rate,
                'image' => $image ? asset('storage/images/product/icon/' . $image->image_path) : null,
            ];
        }));
        return response()->json(['suggestions' => $suggestions]);
    }

    public function searchProductList(Request $request)
    {
        $query = trim($request->input('query', ''));
        $category = trim($request->input('category', ''));
        if (!$query) {
            return response()->json([
                'products' => [],
                'categories' => [],
                'query' => $query,
            ]);
        }
        // Log::info('searchProductList log: ' . $query);
        $searchTerms = explode(' ', $query);
        $booleanQuery = '+' . implode(' +', $searchTerms);
        $perPage = min((int) $request->input('per_page', 20), 100);
        try {
            $productsQuery = Product::whereRaw(
                    "MATCH(title) AGAINST(? IN BOOLEAN MODE)",
                    [$booleanQuery]
                )
                ->orWhere(function ($q) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $q->where('title', 'like', '%' . $term . '%');
                    }
                })
                ->with([
                     'category:id,title,slug',
                    'firstSortedImage:id,product_id,image_path',
                    'images:id,product_id,image_path,sort_order',
                    'inventories' => function ($q) {
                        $q->select('id','product_id','mrp','offer_rate','purchase_rate','sku')
                            ->orderBy('mrp','asc');
                    },
                    'productAttributesValues.attributeValue:id,slug'
                ])
                ->select('id','title','slug','category_id');
            if ($category) {
                $categoryIds = explode(',', $category);
                $productsQuery->whereIn('category_id', $categoryIds);
            }
            $product = $productsQuery->paginate($perPage)->appends($request->query());
            $product->getCollection()->transform(function ($product) {
                $inventory = $product->inventories->first();
                $attribute_slug = optional(
                    $product->productAttributesValues->first()
                )->attributeValue->slug ?? null;

                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'mrp' => $inventory->mrp ?? null,
                    'offer_rate' => $inventory->offer_rate ?? null,
                    'sku' => $inventory->sku ?? null,
                    'attribute_value_slug' => $attribute_slug,
                    'category' => [
                        'title' => optional($product->category)->title,
                        'slug'  => optional($product->category)->slug,
                    ],

                    'image' => $product->firstSortedImage
                        ? $product->firstSortedImage->getSmallImages()
                        : null,

                    // 'images' => $product->images->map(function ($img) {
                    //     return asset('storage/images/product/small/'.$img->image_path);
                    // })->values(),
                ];
            });
            $pagination = [
                'current_page' => $product->currentPage(),
                'total_pages' => $product->lastPage(),
                'per_page' => $product->perPage(),
                'total_products' => $product->total(),
                'next_page_url' => $product->nextPageUrl(),
                'previous_page_url' => $product->previousPageUrl(),
                'has_next_page' => $product->hasMorePages(),
                'has_previous_page' => $product->currentPage() > 1
            ];
            $categories = Category::where(function ($q) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $q->where('title', 'like', '%' . $term . '%');
                    }
                })
                ->limit(10)
                ->get(['id','title','slug']);
            $siteName = "Nova SAC";
            $meta = [
                'title' => ucwords($query) . ' | ' . $siteName,
                'description' => 'Buy ' . $query . ' online from ' . $siteName . 
                '. Explore wide range of premium quality products at best price.',
                'keywords' => $siteName . ', ' . $query . ', buy ' . $query . ', ' . $query . ' online'
            ];
            return response()->json([
                'meta' => $meta,
                'products' => $product->items(),
                'pagination' => $pagination,
                'categories' => $categories,
                'query' => $query,
            ]);

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            return response()->json([
                'products' => [],
                'categories' => [],
                'query' => $query,
            ], 500);
        }
    }

    private function highlightMatch($text, $query)
    {
        if (empty($text) || empty($query)) {
            return e($text);
        }
        $text = e($text);
        $terms = array_filter(explode(' ', $query));
        foreach ($terms as $term) {
            $term = preg_quote($term, '/');

            $text = preg_replace(
                "/($term)/i",
                '<mark>$1</mark>',
                $text
            );
        }
        return $text;
    }
        
}
