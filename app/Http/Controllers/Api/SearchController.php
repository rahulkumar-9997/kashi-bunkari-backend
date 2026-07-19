<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Collection;
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
    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/(.)\1{2,}/', '$1', $value);
    }

    private function isFuzzyMatch(string $term, string $word): bool
    {
        $termN = $this->normalize($term);
        $wordN = $this->normalize($word);
 
        if ($termN === '' || $wordN === '') {
            return false;
        }
 
        if ($termN === $wordN || str_contains($wordN, $termN) || str_contains($termN, $wordN)) {
            return true;
        }
 
        $distance = levenshtein($termN, $wordN);
 
        $threshold = match (true) {
            strlen($wordN) <= 3 => 1,
            strlen($wordN) <= 6 => 2,
            default => 3,
        };
 
        return $distance <= $threshold;
    }

    private function textMatchesTerms(string $text, array $searchTerms): bool
    {
        $words = preg_split('/\s+/', trim($text));
 
        foreach ($searchTerms as $term) {
            foreach ($words as $word) {
                if ($this->isFuzzyMatch($term, $word)) {
                    return true;
                }
            }
        }
 
        return false;
    } 
    
    private function resolveMatchedIds(
        Collection $fastResults,
        string $cacheKey,
        callable $fetchAllFn,
        string $column,
        array $searchTerms,
        int $limit = 5
    ): Collection {
        if ($fastResults->isNotEmpty()) {
            return $fastResults->pluck('id')->take($limit);
        }
         $lookup = Cache::remember($cacheKey, now()->addHours(6), $fetchAllFn);
 
        return $lookup
            ->filter(fn ($row) => $this->textMatchesTerms($row->{$column}, $searchTerms))
            ->pluck('id')
            ->take($limit);
    }
 
    public function searchSuggestions(Request $request)
    {
        $query = trim($request->input('query', ''));
        if (empty($query)) {
            return response()->json(['suggestions' => []]);
        }
 
        Log::info('Search suggestion query: ' . $query);
 
        $searchTerms = preg_split('/\s+/', $query);
        $booleanQuery = '+' . implode(' +', $searchTerms);
 
        /* ---------- Products: fast SQL first ---------- */
        $fastProducts = Product::where('product_status', 1)
            ->where(function ($q) use ($searchTerms, $booleanQuery) {
                $q->whereRaw("MATCH(title) AGAINST(? IN BOOLEAN MODE)", [$booleanQuery]);
                foreach ($searchTerms as $term) {
                    $q->orWhere('title', 'like', '%' . $term . '%');
                }
            })
            ->limit(5)
            ->get(['id', 'title']);
 
        $matchedProductIds = $this->resolveMatchedIds(
            $fastProducts,
            'search_lookup_products',
            fn () => Product::where('product_status', 1)->get(['id', 'title']),
            'title',
            $searchTerms
        );
 
        /* ---------- Categories: fast SQL first ---------- */
        $fastCategories = Category::where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->orWhere('title', 'like', '%' . $term . '%');
            }
        })
            ->limit(5)
            ->get(['id', 'title']);
 
        $matchedCategoryIds = $this->resolveMatchedIds(
            $fastCategories,
            'search_lookup_categories',
            fn () => Category::get(['id', 'title']),
            'title',
            $searchTerms
        );
 
        /* ---------- Attribute values: fast SQL first ---------- */
        $fastAttributeValues = Attribute_values::where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->orWhere('name', 'like', '%' . $term . '%');
            }
        })
            ->limit(5)
            ->get(['id', 'name']);
 
        $matchedAttributeValueIds = $this->resolveMatchedIds(
            $fastAttributeValues,
            'search_lookup_attribute_values',
            fn () => Attribute_values::get(['id', 'name']),
            'name',
            $searchTerms
        );
 
        /* ---------- Fetch full data only for matched IDs ---------- */
        $products = Product::whereIn('products.id', $matchedProductIds)
            ->with([
                'firstImage',
                'category',
                'ProductAttributesValues' => function ($q) {
                    $q->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
                        ->with(['attributeValue:id,slug'])
                        ->orderBy('id');
                }
            ])
            ->leftJoin('inventories', function ($join) {
                $join->on('products.id', '=', 'inventories.product_id')
                    ->whereRaw('inventories.mrp = (SELECT MIN(mrp) FROM inventories WHERE product_id = products.id)');
            })
            ->select('products.*', 'inventories.mrp', 'inventories.offer_rate', 'inventories.purchase_rate', 'inventories.sku')
            ->get();
 
        $categories = Category::whereIn('id', $matchedCategoryIds)->get(['id', 'title']);
 
        $attributeValues = Attribute_values::whereIn('id', $matchedAttributeValueIds)
            ->with('attribute')
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
                'title' => ucwords(strtolower($category->title)),
                'image' => null,
            ];
        }));
 
        /* Add products */
        $suggestions = $suggestions->merge($products->map(function ($product) {
            $image = $product->firstImage;
 
            $attributes_value = null;
            if ($product->ProductAttributesValues->isNotEmpty()) {
                $attributes_value = optional($product->ProductAttributesValues->first()->attributeValue)->slug;
            }
 
            $offer_rate = $product->offer_rate ? 'Rs. ' . $product->offer_rate : null;
 
            return [
                'type' => 'product',
                'title' => ucwords(strtolower($product->title)),
                'slug' => $product->slug,
                'attributes_value_slug' => $attributes_value,
                'category' => optional($product->category)->title,
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
            $siteName = config('app.name');
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
