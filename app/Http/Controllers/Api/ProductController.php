<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute_values;
use App\Models\Attribute;
use App\Models\RelatedProduct;
use App\Models\PrimaryCategory;
use App\Models\Tag;
use App\Models\Label;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductController extends Controller
{
   
    public function productCatalog_remove(Request $request, $categorySlug, $valueSlug,  $attributeSlug)
    {
        try {
            $additional_slug = $categorySlug . '-' . $valueSlug . '-' . $attributeSlug;
            $primary_category = PrimaryCategory::where('additional_slug', $additional_slug)->first();
            if (!$primary_category) {
                Log::warning('Primary category not found for URL: ' . $additional_slug);
            }
            $category = Category::select('id', 'title', 'slug')
                ->where('slug', $categorySlug)
                ->first();
            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found'
                ], 404);
            }
            $attribute_top = Attribute::select('id', 'title', 'slug')
                ->where('slug', $attributeSlug)
                ->first();
            if (!$attribute_top) {
                return response()->json([
                    'status' => false,
                    'message' => 'Attribute not found'
                ], 404);
            }

            $attributeValue = Attribute_values::select('id', 'name', 'slug')
                ->where('slug', $valueSlug)
                ->first();
            if (!$attributeValue) {
                return response()->json([
                    'status' => false,
                    'message' => 'Attribute value not found'
                ], 404);
            }
            $productsQuery = Product::where('category_id', $category->id)
                ->where('product_status', 1);
            /*$productsQuery->whereHas('attributes', function ($query) use ($attribute_top, $attributeValue) {
                $query->where('attributes_id', $attribute_top->id)
                    ->whereHas('values', function ($q) use ($attributeValue) {
                        $q->where('attributes_value_id', $attributeValue->id);
                    });
            });
            */
            /* ================= PRIMARY FILTER ================= */

            $productsQuery->whereHas('ProductAttributesValues.attributeValue', function ($q) use ($attribute_top, $attributeValue) {
                $q->where('slug', $attributeValue->slug)
                ->whereHas('attribute', function ($q2) use ($attribute_top) {
                    $q2->where('slug', $attribute_top->slug);
                });
            });

            /* Apply additional filters from the request */
            if ($request->has('filter')) {
                $filters = $request->except(['filter', 'sort', 'page']);
                foreach ($filters as $attributeSlug => $valueSlugs) {
                    if ($attributeSlug === $attribute_top->slug) {
                        continue;
                    }
                    if (is_string($valueSlugs)) {
                        $valueSlugs = explode(',', $valueSlugs);
                    }
                    foreach ($valueSlugs as $singleSlug) {
                        $productsQuery->whereHas('ProductAttributesValues', function ($q) use ($attributeSlug, $singleSlug) {
                            $q->whereHas('attributeValue', function ($q2) use ($attributeSlug, $singleSlug) {
                                $q2->where('slug', $singleSlug)
                                ->whereHas('attribute', function ($q3) use ($attributeSlug) {
                                    $q3->where('slug', $attributeSlug);
                                });
                            });
                        });
                    }
                }
            }
            /*if ($request->has('filter')) {
                Log::info('Filters attributes value catalog: ' . json_encode($request->query()));
                $filters = $request->except(['filter', 'sort', 'page']);
                foreach ($filters as $filterAttributeSlug => $filterValueSlugs) {
                    if ($filterAttributeSlug !== $attribute_top->slug) {
                        if (is_string($filterValueSlugs)) {
                            $filterValueSlugs = explode(',', $filterValueSlugs);
                        }
                        $attribute = Attribute::where('slug', $filterAttributeSlug)->first();
                        if (!$attribute) {
                            Log::warning("Attribute not found for slug: {$filterAttributeSlug}");
                            continue;
                        }
                        $valueIds = Attribute_values::whereIn('slug', $filterValueSlugs)->pluck('id')->toArray();
                        $productsQuery->whereHas('attributes', function ($query) use ($attribute, $valueIds) {
                            $query->where('attributes_id', $attribute->id)
                                ->whereHas('values', function ($q) use ($valueIds) {
                                    $q->whereIn('attributes_value_id', $valueIds);
                                });
                        });
                    }
                }
            }
            */
            if ($request->has('sort')) {
                $sortOption = $request->get('sort');
                switch ($sortOption) {
                    case 'new-arrivals':
                        $productsQuery->orderBy('created_at', 'desc');
                        break;
                    case 'price-low-to-high':
                        $productsQuery->orderByRaw('ISNULL(inventories.offer_rate), inventories.offer_rate ASC');
                        break;
                    case 'price-high-to-low':
                        $productsQuery->orderByRaw('ISNULL(inventories.offer_rate), inventories.offer_rate DESC');
                        break;
                    case 'a-to-z-order':
                        $productsQuery->orderBy('products.title', 'asc');
                        break;
                    default:
                        $productsQuery->orderBy('products.id', 'desc');
                        break;
                }
            } else {
                $productsQuery->orderBy('products.created_at', 'desc');
            }
            $products = $productsQuery->with([
                'category:id,title,slug',
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image_path', 'sort_order')
                        ->orderBy('sort_order');
                },
                'ProductAttributesValues' => function ($query) {
                    $query->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
                        ->with([
                            'attributeValue:id,slug',
                            'productAttribute:id,attributes_id'
                        ])
                        ->orderBy('id');
                },
            ])
                ->leftJoin('inventories', function ($join) {
                    $join->on('products.id', '=', 'inventories.product_id')
                        ->whereRaw('inventories.mrp = (SELECT MIN(mrp) FROM inventories WHERE product_id = products.id)');
                })
                ->select(
                    'products.id',
                    'products.title',
                    'products.slug',
                    'products.category_id',
                    'products.created_at',

                    'inventories.mrp',
                    'inventories.offer_rate',
                    'inventories.purchase_rate',
                    'inventories.sku',
                    'inventories.stock_quantity'
                )
                ->paginate(30)
                ->through(function ($product) {
                    $attributes_value = null;
                    if ($product->ProductAttributesValues->isNotEmpty()) {
                        $attributes_value = optional($product->ProductAttributesValues->first()->attributeValue)->slug;
                    }
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'mrp' => $product->mrp,
                        'offer_price' => $product->offer_rate,
                        'sku' => $product->sku,
                        'stock_quantity' => $product->stock_quantity,
                        'image' => isset($product->images[0])
                            ? asset('storage/images/product/small/' . $product->images[0]->image_path)
                            : null,
                        'attributes_value_slug' => $attributes_value
                    ];
                });
            /* Fetch attributes with values for the filter list (mapped attributes and counts) */
            $attributes_with_values_for_filter_list = $category->attributes()
            ->select('attributes.id', 'attributes.title', 'attributes.slug')
            ->whereHas('AttributesValues', function ($query) use ($category, $attribute_top, $attributeValue) {
                $query->whereHas('map_attributes_value_to_categories', function ($q) use ($category) {
                        $q->where('category_id', $category->id);
                    })
                    ->whereHas('productAttributesValues', function ($q) use ($category, $attribute_top, $attributeValue) {
                        $q->whereHas('product', function ($q) use ($category, $attribute_top, $attributeValue) {
                            $q->where('category_id', $category->id)
                            ->whereHas('attributes', function ($query) use ($attribute_top, $attributeValue) {
                                $query->where('attributes_id', $attribute_top->id)
                                        ->whereHas('values', function ($q) use ($attributeValue) {
                                            $q->where('attributes_value_id', $attributeValue->id);
                                        });
                            });
                        });
                    });
            })
            ->with(['AttributesValues' => function ($query) use ($category, $attribute_top, $attributeValue) {
                $query->select('id', 'attributes_id', 'name', 'slug')
                    ->whereHas('map_attributes_value_to_categories', function ($q) use ($category) {
                        $q->where('category_id', $category->id);
                    })
                    ->withCount(['productAttributesValues' => function ($q) use ($category, $attribute_top, $attributeValue) {
                        $q->whereHas('product', function ($q) use ($category, $attribute_top, $attributeValue) {
                            $q->where('category_id', $category->id)
                            ->whereHas('attributes', function ($query) use ($attribute_top, $attributeValue) {
                                $query->where('attributes_id', $attribute_top->id)
                                ->whereHas('values', function ($q) use ($attributeValue) {
                                    $q->where('attributes_value_id', $attributeValue->id);
                                });
                            });
                        });
                    }])
                    ->having('product_attributes_values_count', '>', 0)
                    ->orderBy('name');
            }])
            ->orderBy('title')
            ->get()
            ->filter(function ($attribute) {
                return $attribute->AttributesValues->isNotEmpty();
            })
            ->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'title' => $attribute->title,
                    'slug' => $attribute->slug,
                    'values' => $attribute->AttributesValues->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'name' => $value->name,
                            'slug' => $value->slug
                        ];
                    })
                ];
            })
            ->values();
            $siteName = "Kasi Bunkari";
            $categoryTitle = $category->title;
            $attributeName = $attributeValue->name;
            $fallbackTitle = $attributeName . ' ' . $categoryTitle . ' | ' . $siteName;
            $fallbackDescription = 'Buy ' . $attributeName . ' ' . $categoryTitle . ' from ' . $siteName . '. Explore premium quality ' . $categoryTitle . ' crafted with the best ' . $attributeName . '.';
            $title = $primary_category && !empty($primary_category->meta_title)
                ? $primary_category->meta_title
                : $fallbackTitle;
            $description = $primary_category && !empty($primary_category->meta_description)
                ? $primary_category->meta_description
                : $fallbackDescription;
            if (!$primary_category || empty($primary_category->meta_description)) {
                if (!empty($primary_category->short_heading)) {
                    $description = $primary_category->short_heading . ' - ' . $fallbackDescription;
                }
            }
            $keywords = $siteName . ', ' . $categoryTitle . ', ' . $attributeName . ' ' . $categoryTitle;
            $title = \Illuminate\Support\Str::limit($title, 60, '');
            $description = \Illuminate\Support\Str::limit($description, 160, '');
            $meta = [
                'title' => $title,
                'description' => $description,
                'keywords' => $keywords
            ];

            $pagination = [
                'current_page' => $products->currentPage(),
                'total_pages' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total_products' => $products->total(),
                'next_page_url' => $products->nextPageUrl(),
                'previous_page_url' => $products->previousPageUrl(),
                'has_next_page' => $products->hasMorePages(),
                'has_previous_page' => $products->currentPage() > 1
            ];
            $primary_category_data = [
                'title' => $primary_category ? $primary_category->title : null,
                'short_content' => $primary_category ? $primary_category->short_heading : null,
                'long_content' => $primary_category ? $primary_category->primary_category_description : null,
            ];
            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => [
                    'meta' => $meta,
                    'primary_category' => $primary_category_data,
                    'category' => $category,
                    'attribute' => $attribute_top,
                    'attribute_value' => $attributeValue,
                    'products' => $products->items(),
                    'pagination' => $pagination,
                    'product_filters' => $attributes_with_values_for_filter_list
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product catalog: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function productCategoryCatalog_remove(Request $request, $categorySlug)
    {
        try {
            $primary_category = PrimaryCategory::where('additional_slug', $categorySlug)->first();
            if (!$primary_category) {
                Log::warning('Primary category not found for URL: ' . $categorySlug);
            }
            $category = Category::where('slug', $categorySlug)
            ->select('id', 'title', 'slug')
            ->first();
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }
            /* ================= BASE QUERY ================= */
            $productsQuery = Product::where('category_id', $category->id)->where('product_status', 1);
            /* ================= FILTER ================= */
            /*if ($request->has('filter')) {
                Log::info('Filters category catalog: ' . json_encode($request->query()));
                $filters = $request->except(['filter', 'sort', 'page']);
                foreach ($filters as $attributeSlug => $valueSlugs) {
                    if (is_string($valueSlugs)) {
                        $valueSlugs = explode(',', $valueSlugs);
                    }
                    $attribute = Attribute::where('slug', $attributeSlug)->first();
                    if (!$attribute) {
                        Log::warning("Attribute not found for slug: {$attributeSlug}");
                        continue;
                    }
                    $valueIds = Attribute_values::whereIn('slug', $valueSlugs)->pluck('id')->toArray();
                    $productsQuery->whereHas('attributes', function ($query) use ($attribute, $valueIds) {
                        $query->where('attributes_id', $attribute->id)
                            ->whereHas('values', function ($q) use ($valueIds) {
                                $q->whereIn('attributes_value_id', $valueIds);
                            });
                    });
                }
            } 
            */
            if ($request->has('filter')) {
                Log::info('Filters category catalog: ' . json_encode($request->query()));
                $filters = $request->except(['filter', 'sort', 'page']);
                foreach ($filters as $attributeSlug => $valueSlugs) {
                    if (is_string($valueSlugs)) {
                        $valueSlugs = explode(',', $valueSlugs);
                    }
                    foreach ($valueSlugs as $valueSlug) {
                        $productsQuery->whereHas('ProductAttributesValues', function ($q) use ($attributeSlug, $valueSlug) {
                            $q->whereHas('attributeValue', function ($q2) use ($attributeSlug, $valueSlug) {
                            $q2->where('slug', $valueSlug)
                                ->whereHas('attribute', function ($q3) use ($attributeSlug) {
                                    $q3->where('slug', $attributeSlug);
                                });
                            });
                        });
                    }
                }
            }
            /* ================= SORT ================= */          
            if ($request->has('sort')) {
                $sortOption = $request->get('sort');
                switch ($sortOption) {
                    case 'new-arrivals':
                        $productsQuery->orderBy('created_at', 'desc');
                        break;
                    case 'price-low-to-high':
                        $productsQuery->orderByRaw('ISNULL(inventories.offer_rate), inventories.offer_rate ASC');
                        break;
                    case 'price-high-to-low':
                        $productsQuery->orderByRaw('ISNULL(inventories.offer_rate), inventories.offer_rate DESC');
                        break;
                    case 'a-to-z-order':
                        $productsQuery->orderBy('products.title', 'asc');
                        break;
                    default:
                        $productsQuery->orderBy('products.id', 'desc');
                        break;
                }
            } else {
                $productsQuery->orderBy('created_at', 'desc');
            }
            /* ================= PRODUCTS ================= */
            $products = $productsQuery->with([
                'category:id,title,slug',
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image_path', 'sort_order')
                        ->orderBy('sort_order');
                },
                'ProductAttributesValues' => function ($query) {
                    $query->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
                        ->with([
                            'attributeValue:id,slug',
                            'productAttribute:id,attributes_id'
                        ])
                        ->orderBy('id');
                }
            ])
                ->leftJoin('inventories', function ($join) {
                    $join->on('products.id', '=', 'inventories.product_id')
                        ->whereRaw('inventories.mrp = (SELECT MIN(mrp) FROM inventories WHERE product_id = products.id)');
                })
                ->select(
                    'products.id',
                    'products.title',
                    'products.slug',
                    'products.category_id',
                    'products.created_at',

                    'inventories.mrp',
                    'inventories.offer_rate',
                    'inventories.purchase_rate',
                    'inventories.sku',
                    'inventories.stock_quantity'
                )
                ->paginate(12)
                ->through(function ($product) {
                    $attributes_value = null;
                    if ($product->ProductAttributesValues->isNotEmpty()) {
                        $attributes_value = optional($product->ProductAttributesValues->first()->attributeValue)->slug;
                    }
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'mrp' => $product->mrp,
                        'offer_price' => $product->offer_rate,
                        'sku' => $product->sku,
                        'stock_quantity' => $product->stock_quantity,
                        'image' => isset($product->images[0])
                            ? asset('storage/images/product/small/' . $product->images[0]->image_path)
                            : null,
                        'attributes_value_slug' => $attributes_value
                    ];
                });
            /* ================= FILTER LIST ================= */
            $attributes_with_values_for_filter_list = $category->attributes()
            ->select(
                'attributes.id',
                'attributes.title',
                'attributes.slug'
            )
            ->with(['AttributesValues' => function ($query) use ($category) {
                $query->select(
                    'id',
                    'attributes_id',
                    'name',
                    'slug'
                )
                ->whereHas('map_attributes_value_to_categories', function ($q) use ($category) {
                    $q->where('category_id', $category->id);
                })
                    ->withCount(['productAttributesValues' => function ($q) use ($category) {
                        $q->whereHas('product', function ($q) use ($category) {
                            $q->where('category_id', $category->id);
                        });
                    }])
                    ->orderBy('name');
            }])
            ->orderBy('title')
            ->get()
            ->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'title' => $attribute->title,
                    'slug' => $attribute->slug,
                    'values' => $attribute->AttributesValues->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'name' => $value->name,
                            'slug' => $value->slug
                        ];
                    })
                ];
            });
            /* ================= META ================= */
            $siteName = "Kasi Bunkari";
            $categoryTitle = $primary_category->title ?? $category->title;
            $shortHeading = $primary_category->short_heading ?? null;
            $titleRaw = $primary_category && !empty($primary_category->meta_title)
                ? $primary_category->meta_title
                : $categoryTitle . ' | ' . $siteName;
            $title = \Illuminate\Support\Str::limit($titleRaw, 60, '');
            $descriptionRaw = $primary_category && !empty($primary_category->meta_description)
                ? $primary_category->meta_description
                : (
                    $shortHeading
                    ? $shortHeading . ' - Explore and buy ' . $categoryTitle . ' at best prices from ' . $siteName
                    : 'Explore and buy ' . $categoryTitle . ' at best prices from ' . $siteName . '. High quality products with fast delivery.'
                );
            $description = \Illuminate\Support\Str::limit($descriptionRaw, 160, '');
            $keywords = $siteName . ', ' . $categoryTitle;
            $meta = [
                'title' => $title,
                'description' => $description,
                'keywords' => $keywords
            ];
            $primary_category_data = [
                'title' => $primary_category ? $primary_category->title : null,
                'short_content' => $primary_category ? $primary_category->short_heading : null,
                'long_content' => $primary_category ? $primary_category->primary_category_description : null,
            ];
            $pagination = [
                'current_page' => $products->currentPage(),
                'total_pages' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total_products' => $products->total(),
                'next_page_url' => $products->nextPageUrl(),
                'previous_page_url' => $products->previousPageUrl(),
                'has_next_page' => $products->hasMorePages(),
                'has_previous_page' => $products->currentPage() > 1
            ];
            return response()->json([
                'success' => true,
                'message' => 'Products category retrieved successfully',
                'data' => [
                    'meta' => $meta,
                    'primary_category' => $primary_category_data,
                    'category' => $category,
                    'products' => $products->items(),
                    'pagination' => $pagination,
                    'product_filters' => $attributes_with_values_for_filter_list
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product catalog: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function productDetails(Request $request, $product_slug, $attribute_value)
	{
		try {
			if (config('app.debug')) {
				DB::enableQueryLog();
			}

			if (empty($product_slug) || empty($attribute_value)) {
				return response()->json([
					'success' => false,
					'message' => 'Product slug and attribute value are required'
				], 400);
			}

			$attributeValue = Attribute_values::where('slug', $attribute_value)->first();
			if (!$attributeValue) {
				return response()->json([
					'success' => false,
					'message' => 'Attribute value not found'
				], 404);
			}

			$attribute = Attribute::where('id', $attributeValue->attributes_id)->first();
			if (!$attribute) {
				Log::warning('Attribute not found for attribute_value slug: ' . $attribute_value);
				return response()->json([
					'success' => false,
					'message' => 'Attribute not found for this attribute value'
				], 404);
			}

			$product_details = Product::with([
				'images' => function ($query) {
					$query->select('id', 'product_id', 'image_path', 'sort_order')
						->orderBy('sort_order');
				},
				'category:id,title,slug',
				'attributes.attribute:id,title,slug',
				'attributes.values.attributeValue:id,name,slug,attributes_id',
				'additionalFeatures.feature:id,title',
				'reviews.reviewFiles',
			])
				->leftJoin('inventories', function ($join) {
					$join->on('products.id', '=', 'inventories.product_id')
						->whereRaw('inventories.mrp = (SELECT MIN(mrp) FROM inventories WHERE product_id = products.id)');
				})
				->select(
					'products.id',
					'products.title',
					'products.slug',
					'products.category_id',
					'products.product_short_description',
					'products.product_description',
					'products.product_specification',
					'products.meta_title',
					'products.meta_description',
					'products.video_id',
					'inventories.mrp',
					'inventories.offer_rate',
					'inventories.purchase_rate',
					'inventories.sku',
					'inventories.stock_quantity'
				)
				->where('products.slug', $product_slug)
				->first();

			if (!$product_details) {
				return response()->json([
					'success' => false,
					'message' => 'Product not found'
				], 404);
			}
			Product::whereKey($product_details->id)->increment('visitor_count');
			$imageThumbs = $product_details->images->map(function ($img) {
				return [
					'id' => $img->id,
					'image_thumb' => asset('storage/images/product/thumb/' . $img->image_path),
				];
			});

			$imageLarges = $product_details->images->map(function ($img) {
				return [
					'id' => $img->id,
					'image_large' => asset('storage/images/product/large/' . $img->image_path),
				];
			});

			$meta = $this->buildProductMeta($product_details, $attributeValue);

			$productData = [
				'id' => $product_details->id,
				'title' => $product_details->title,
				'slug' => $product_details->slug,
				'category_id' => $product_details->category_id,
				'product_short_description' => $product_details->product_short_description,
				'product_description' => $product_details->product_description,
				'product_specification' => $product_details->product_specification,
				'video_id' => $product_details->video_id,
				'mrp' => $product_details->mrp,
				'offer_rate' => $product_details->offer_rate,
				'purchase_rate' => $product_details->purchase_rate,
				'sku' => $product_details->sku,
				'stock_quantity' => $product_details->stock_quantity,
				'image_thumbs' => $imageThumbs,
				'image_larges' => $imageLarges,
				'category' => $product_details->category,
				'attributes' => $product_details->attributes,
				'additional_features' => $product_details->additionalFeatures->map(function ($item) {
					return [
						'id' => $item->feature->id ?? null,
						'title' => $item->feature->title ?? null,
						'value' => $item->product_additional_feature_value,
					];
				})->values(),
			];

			$categoryId = $product_details->category->id ?? null;
			$related_products_query = Product::with([
				'category:id,title,slug',
				'firstSortedImage:id,product_id,image_path',
				'ProductAttributesValues' => function ($query) use ($attributeValue) {
					$query->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
						->where('attributes_value_id', $attributeValue->id)
						->with([
							'attributeValue:id,slug'
						])
						->orderBy('id');
				}
			])
				->leftJoin('inventories', function ($join) {
					$join->on('products.id', '=', 'inventories.product_id')
						->whereRaw('inventories.mrp = (SELECT MIN(mrp) FROM inventories WHERE product_id = products.id)');
				})
				->select(
					'products.id',
					'products.title',
					'products.slug',
					'products.category_id',
					'inventories.mrp',
					'inventories.offer_rate',
					'inventories.purchase_rate',
					'inventories.sku',
					'inventories.stock_quantity'
				)->where('products.product_status', 1);

			if ($categoryId) {
				$related_products_query->where('products.category_id', $categoryId);
			}

			$related_products = $related_products_query
				->where('products.id', '!=', $product_details->id)
				->whereHas('productAttributesValues', function ($query) use ($attributeValue) {
					$query->where('attributes_value_id', $attributeValue->id);
				})
				->inRandomOrder()
				->limit(8)
				->get()
				->map(function ($product) {
					$attribute_slug = optional(
						optional($product->ProductAttributesValues->first())->attributeValue
					)->slug;

					return [
						'id' => $product->id,
						'category' => [
							'title' => $product->category->title ?? null,
							'slug'  => $product->category->slug ?? null,
						],
						'title' => $product->title,
						'slug' => $product->slug,
						'attribute_value_slug' => $attribute_slug,
						'category_title' => $product->category->title ?? null,
						'image' => $product->firstSortedImage ? $product->firstSortedImage->getLargeImages() : null,
						'mrp' => $product->mrp,
						'offer_rate' => $product->offer_rate,
						'purchase_rate' => $product->purchase_rate,
						'sku' => $product->sku,
						'stock_quantity' => $product->stock_quantity,
					];
				})->values();

			$variantIds = RelatedProduct::where('product_id', $product_details->id)->pluck('variant_id');
			$other_related_products = [];
			if ($variantIds->isNotEmpty()) {
				$otherRelatedProducts = RelatedProduct::with([
					'product' => function ($query) use ($attributeValue) {
						$query->select('id', 'title', 'slug')
							->with([
								'ProductAttributesValues' => function ($q) use ($attributeValue) {
									$q->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
										->where('attributes_value_id', $attributeValue->id)
										->with([
											'attributeValue:id,name,slug'
										])
										->orderBy('id');
								}
							]);
					}
				])
					->whereIn('variant_id', $variantIds)
					->select('product_id', 'variant_id', 'title', 'group_title', 'description')
					->get();

				$groupedProducts = $otherRelatedProducts->groupBy('group_title');

				foreach ($groupedProducts as $groupTitle => $items) {
					$options = [];

					foreach ($items as $item) {
						if ($item->product && $item->product->ProductAttributesValues->isNotEmpty()) {
							foreach ($item->product->ProductAttributesValues as $pav) {
								if ($pav->attributeValue) {
									$options[] = [
										'group_name' => $item->title,
										'product_slug' => $item->product->slug,
										'attribute_value_slug' => $pav->attributeValue->slug,
									];
								}
							}
						}
					}
					$other_related_products[$groupTitle] = $options;
				}
			}

			return response()->json([
				'success' => true,
				'message' => 'Product details fetched successfully',
				'data' => [
					'meta' => $meta,
					'attribute' => [
						'id' => $attribute->id,
						'title' => $attribute->title,
						'slug' => $attribute->slug
					],
					'attributes_value_name' => [
						'id' => $attributeValue->id,
						'title' => $attributeValue->name,
						'slug' => $attributeValue->slug
					],
					'product_details' => $productData,
					'related_products' => $related_products,
					'other_related_products' => (object) $other_related_products,
				]
			], 200);

		} catch (\Throwable $e) {
			Log::error('Product Details API Error: ' . $e->getMessage(), [
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString(),
				'sql' => config('app.debug') ? DB::getQueryLog() : null,
			]);

			$payload = [
				'success' => false,
				'message' => 'Something went wrong while loading the product details.'
			];

			if (config('app.debug')) {
				$payload['debug'] = [
					'exception' => $e->getMessage(),
					'file' => $e->getFile(),
					'line' => $e->getLine(),
				];
			}

			return response()->json($payload, 500);
		}
	}
	
	
	private function buildProductMeta($product_details, $attributeValue)
	{
		$siteName = "Kasi Bunkari";
		$categoryTitle = $product_details->category->title ?? null;
	 
		$fallbackTitle = $product_details->title
			. ($attributeValue->name ? ' - ' . $attributeValue->name : '');
	 
		$fallbackDescription = $product_details->product_short_description
			?: 'Buy ' . $product_details->title . ($categoryTitle ? ' in ' . $categoryTitle : '')
				. ' from ' . $siteName . '. Best quality, best price.';
	 
		$title = !empty($product_details->meta_title) ? $product_details->meta_title : $fallbackTitle;
		$description = !empty($product_details->meta_description) ? $product_details->meta_description : $fallbackDescription;
	 
		$keywordsParts = array_filter([
			$siteName,
			$product_details->title,
			$categoryTitle,
			$attributeValue->name,
		]);
	 
		return [
			'title' => \Illuminate\Support\Str::limit($title, 60, ''),
			'description' => \Illuminate\Support\Str::limit(strip_tags($description), 160, ''),
			'keywords' => implode(', ', $keywordsParts),
		];
	}
		
	
	/**New implement */
    public function productCatalogForAllParams(Request $request, $first, $second = null, $third = null)
    {
        try {
            /* category + attribute + value */
            if ($second !== null && $third !== null) {
                return $this->buildCategoryAttributeValueResponse($request, $first, $second, $third);
            }

            /* Case: only one slug given -> could be Tag, Category, or Label */
            if ($second === null && $third === null) {

                $tag = Tag::select('id', 'title', 'slug', 'meta_title', 'meta_description', 'content')
                    ->where('slug', $first)
                    ->where('status', true)
                    ->first();
                if ($tag) {
                    return $this->buildTagResponse($request, $tag);
                }

                $category = Category::select('id', 'title', 'slug')->where('slug', $first)->first();
                if ($category) {
                    return $this->buildCategoryResponse($request, $category);
                }

                $label = Label::select('id', 'title', 'slug')
                    ->where('slug', $first)
                    ->where('status', 1)
                    ->first();
                if ($label) {
                    return $this->buildLabelResponse($request, $label);
                }

                return $this->notFoundResponse('No category, tag, or label found for "' . $first . '".');
            }
            return $this->invalidRequestResponse('This URL format is not supported. Expected /shop/{slug} or /shop/{category}/{attribute}/{value}.');

        } catch (\Throwable $e) {
            return $this->serverErrorResponse($e, 'Something went wrong while loading the catalog.');
        }
    }

    /*BRANCH 1: TAG   (new-arrival, trending) */
    private function buildTagResponse(Request $request, Tag $tag)
    {
        $productsQuery = Product::where('product_status', 1)
            ->whereHas('tags', function ($q) use ($tag) {
                $q->where('tags.id', $tag->id);
            });

        $this->applyDynamicFilters($request, $productsQuery);
        $this->applySort($request, $productsQuery);

        $products = $this->getPaginatedProducts($productsQuery, 30);
        $filters = $this->getTagFilterList($tag);

        $title = !empty($tag->meta_title) ? $tag->meta_title : $tag->title;
        $description = !empty($tag->meta_description)
            ? $tag->meta_description
            : 'Explore our ' . $tag->title . ' collection at Kasi Bunkari.';

        return response()->json([
            'success' => true,
            'message' => 'Tag products retrieved successfully',
            'data' => [
                'type' => 'tag',
                'meta' => [
                    'title' => Str::limit($title, 60, ''),
                    'description' => Str::limit($description, 160, ''),
                    'keywords' => 'Kasi Bunkari, ' . $tag->title
                ],
                'tag' => [
                    'id' => $tag->id,
                    'title' => $tag->title,
                    'slug' => $tag->slug,
                    'content' => $tag->content,
                ],
                'products' => $products->items(),
                'pagination' => $this->buildPagination($products),
                'product_filters' => $filters
            ]
        ]);
    }

    /*  BRANCH 2: CATEGORY ONLY */
    private function buildCategoryResponse(Request $request, Category $category)
    {
        $primary_category = PrimaryCategory::where('additional_slug', $category->slug)->first();

        $productsQuery = Product::where('category_id', $category->id)->where('product_status', 1);

        $this->applyDynamicFilters($request, $productsQuery);
        $this->applySort($request, $productsQuery);

        $products = $this->getPaginatedProducts($productsQuery, 12);
        $filters = $this->getCategoryFilterList($category, null, null, false);
        $meta = $this->buildMeta($primary_category, $primary_category->title ?? $category->title, null);

        return response()->json([
            'success' => true,
            'message' => 'Category products retrieved successfully',
            'data' => [
                'type' => 'category',
                'meta' => $meta,
                'primary_category' => $this->buildPrimaryCategoryData($primary_category),
                'category' => $category,
                'products' => $products->items(),
                'pagination' => $this->buildPagination($products),
                'product_filters' => $filters
            ]
        ]);
    }

    /*  BRANCH 3: CATEGORY + ATTRIBUTE + VALUE */
    private function buildCategoryAttributeValueResponse(Request $request, $categorySlug, $attributeSlug, $valueSlug)
    {
        $additional_slug = $categorySlug . '-' . $valueSlug . '-' . $attributeSlug;
        $primary_category = PrimaryCategory::where('additional_slug', $additional_slug)->first();

        $category = Category::select('id', 'title', 'slug')->where('slug', $categorySlug)->first();
        if (!$category) {
            return $this->notFoundResponse('Category "' . $categorySlug . '" was not found.');
        }

        $attribute_top = Attribute::select('id', 'title', 'slug')->where('slug', $attributeSlug)->first();
        if (!$attribute_top) {
            return $this->notFoundResponse('Attribute "' . $attributeSlug . '" was not found.');
        }

        $attributeValue = Attribute_values::select('id', 'name', 'slug')->where('slug', $valueSlug)->first();
        if (!$attributeValue) {
            return $this->notFoundResponse('Attribute value "' . $valueSlug . '" was not found.');
        }

        $productsQuery = Product::where('category_id', $category->id)->where('product_status', 1);

        $productsQuery->whereHas('productAttributesValues.attributeValue', function ($q) use ($attribute_top, $attributeValue) {
            $q->where('slug', $attributeValue->slug)
                ->whereHas('attribute', function ($q2) use ($attribute_top) {
                    $q2->where('slug', $attribute_top->slug);
                });
        });

        $this->applyDynamicFilters($request, $productsQuery, $attribute_top->slug);
        $this->applySort($request, $productsQuery);

        $products = $this->getPaginatedProducts($productsQuery, 30);
        $filters = $this->getCategoryFilterList($category, $attribute_top, $attributeValue, true);
        $meta = $this->buildMeta($primary_category, $primary_category->title ?? $category->title, $attributeValue->name);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => [
                'type' => 'category_attribute_value',
                'meta' => $meta,
                'primary_category' => $this->buildPrimaryCategoryData($primary_category),
                'category' => $category,
                'attribute' => $attribute_top,
                'attribute_value' => $attributeValue,
                'products' => $products->items(),
                'pagination' => $this->buildPagination($products),
                'product_filters' => $filters
            ]
        ]);
    }

    /*  BRANCH 4: LABEL   (e.g. new-arrivals label, best-seller label) */
    private function buildLabelResponse(Request $request, Label $label)
    {
        $productsQuery = Product::where('product_status', 1)
            ->where('label_id', $label->id);

        $this->applyDynamicFilters($request, $productsQuery);
        $this->applySort($request, $productsQuery);

        $products = $this->getPaginatedProducts($productsQuery, 30);
        $filters = $this->getLabelFilterList($label);

        $siteName = "Kasi Bunkari";
        $title = $label->title;
        $description = 'Explore our ' . $label->title . ' collection at ' . $siteName . '.';

        return response()->json([
            'success' => true,
            'message' => 'Label products retrieved successfully',
            'data' => [
                'type' => 'label',
                'meta' => [
                    'title' => Str::limit($title, 60, ''),
                    'description' => Str::limit($description, 160, ''),
                    'keywords' => $siteName . ', ' . $label->title
                ],
                'label' => [
                    'id' => $label->id,
                    'title' => $label->title,
                    'slug' => $label->slug,
                ],
                'products' => $products->items(),
                'pagination' => $this->buildPagination($products),
                'product_filters' => $filters
            ]
        ]);
    }

    /* SHARED HELPERS (used by all 4 branches) */
    private function applyDynamicFilters(Request $request, $productsQuery, $excludeAttributeSlug = null)
    {
        if (!$request->has('filter')) {
            return;
        }
        $filters = $request->except(['filter', 'sort', 'page']);
        foreach ($filters as $filterAttrSlug => $valueSlugs) {
            if ($excludeAttributeSlug && $filterAttrSlug === $excludeAttributeSlug) {
                continue;
            }
            if (is_string($valueSlugs)) {
                $valueSlugs = explode(',', $valueSlugs);
            }
            foreach ($valueSlugs as $singleSlug) {
                $productsQuery->whereHas('productAttributesValues', function ($q) use ($filterAttrSlug, $singleSlug) {
                    $q->whereHas('attributeValue', function ($q2) use ($filterAttrSlug, $singleSlug) {
                        $q2->where('slug', $singleSlug)
                            ->whereHas('attribute', function ($q3) use ($filterAttrSlug) {
                                $q3->where('slug', $filterAttrSlug);
                            });
                    });
                });
            }
        }
    }

    private function applySort(Request $request, $productsQuery)
    {
        $sortOption = $request->get('sort');
        switch ($sortOption) {
            case 'new-arrivals':
                $productsQuery->orderBy('created_at', 'desc');
                break;
            case 'price-low-to-high':
                $productsQuery->orderByRaw('ISNULL(inventories.offer_rate), inventories.offer_rate ASC');
                break;
            case 'price-high-to-low':
                $productsQuery->orderByRaw('ISNULL(inventories.offer_rate), inventories.offer_rate DESC');
                break;
            case 'a-to-z-order':
                $productsQuery->orderBy('products.title', 'asc');
                break;
            default:
                $productsQuery->orderBy('products.created_at', 'desc');
                break;
        }
    }

    private function getPaginatedProducts($productsQuery, $perPage)
    {
        return $productsQuery->with([
                'category:id,title,slug',
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image_path', 'sort_order')->orderBy('sort_order');
                },
                'productAttributesValues' => function ($query) {
                    $query->select('id', 'product_id', 'product_attribute_id', 'attributes_value_id')
                        ->with(['attributeValue:id,slug', 'productAttribute:id,attributes_id'])
                        ->orderBy('id');
                },
            ])
            ->leftJoin('inventories', function ($join) {
                $join->on('products.id', '=', 'inventories.product_id')
                    ->whereRaw('inventories.mrp = (SELECT MIN(mrp) FROM inventories WHERE product_id = products.id)');
            })
            ->select(
                'products.id', 'products.title', 'products.slug', 'products.category_id', 'products.created_at',
                'inventories.mrp', 'inventories.offer_rate', 'inventories.purchase_rate',
                'inventories.sku', 'inventories.stock_quantity'
            )
            ->paginate($perPage)
            ->through(function ($product) {
                $attributes_value = null;
                if ($product->productAttributesValues->isNotEmpty()) {
                    $attributes_value = optional($product->productAttributesValues->first()->attributeValue)->slug;
                }
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'mrp' => $product->mrp,
                    'offer_price' => $product->offer_rate,
                    'sku' => $product->sku,
                    'stock_quantity' => $product->stock_quantity,
                    'image' => isset($product->images[0])
                        ? asset('storage/images/product/small/' . $product->images[0]->image_path)
                        : null,
                    'attributes_value_slug' => $attributes_value,
                    'category' => optional($product->category)->title,
                ];
            });
    }

    private function buildPagination($products)
    {
        return [
            'current_page' => $products->currentPage(),
            'total_pages' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total_products' => $products->total(),
            'next_page_url' => $products->nextPageUrl(),
            'previous_page_url' => $products->previousPageUrl(),
            'has_next_page' => $products->hasMorePages(),
            'has_previous_page' => $products->currentPage() > 1
        ];
    }

    private function buildPrimaryCategoryData($primary_category)
    {
        return [
            'title' => $primary_category ? $primary_category->title : null,
            'short_content' => $primary_category ? $primary_category->short_heading : null,
            'long_content' => $primary_category ? $primary_category->primary_category_description : null,
        ];
    }

    /**
     * NOTE: Site name removed from the meta TITLE (per request).
     * It's still kept in description/keywords for SEO context — remove those too if not wanted.
     */
    private function buildMeta($primary_category, $categoryTitle, $attributeName = null)
    {
        $siteName = "Kasi Bunkari";
        $shortHeading = $primary_category->short_heading ?? null;

        if ($attributeName) {
            $fallbackTitle = $attributeName . ' ' . $categoryTitle;
            $fallbackDescription = 'Buy ' . $attributeName . ' ' . $categoryTitle . ' from ' . $siteName
                . '. Explore premium quality ' . $categoryTitle . ' crafted with the best ' . $attributeName . '.';
            $keywords = $siteName . ', ' . $categoryTitle . ', ' . $attributeName . ' ' . $categoryTitle;
        } else {
            $fallbackTitle = $categoryTitle;
            $fallbackDescription = $shortHeading
                ? $shortHeading . ' - Explore and buy ' . $categoryTitle . ' at best prices from ' . $siteName
                : 'Explore and buy ' . $categoryTitle . ' at best prices from ' . $siteName . '. High quality products with fast delivery.';
            $keywords = $siteName . ', ' . $categoryTitle;
        }
        $title = $primary_category && !empty($primary_category->meta_title) ? $primary_category->meta_title : $fallbackTitle;
        $description = $primary_category && !empty($primary_category->meta_description) ? $primary_category->meta_description : $fallbackDescription;

        return [
            'title' => Str::limit($title, 60, ''),
            'description' => Str::limit($description, 160, ''),
            'keywords' => $keywords
        ];
    }

    private function getCategoryFilterList($category, $attribute_top, $attributeValue, $isFiltered)
    {
        $query = $category->attributes()->select('attributes.id', 'attributes.title', 'attributes.slug');

        if ($isFiltered) {
            $query->whereHas('AttributesValues', function ($q) use ($category, $attribute_top, $attributeValue) {
                $q->whereHas('map_attributes_value_to_categories', function ($q2) use ($category) {
                        $q2->where('category_id', $category->id);
                    })
                    ->whereHas('productAttributesValues', function ($q2) use ($category, $attribute_top, $attributeValue) {
                        $q2->whereHas('product', function ($q3) use ($category, $attribute_top, $attributeValue) {
                            $q3->where('category_id', $category->id)
                                ->whereHas('productAttributesValues.attributeValue', function ($q4) use ($attribute_top, $attributeValue) {
                                    $q4->where('slug', $attributeValue->slug)
                                        ->whereHas('attribute', function ($q5) use ($attribute_top) {
                                            $q5->where('slug', $attribute_top->slug);
                                        });
                                });
                        });
                    });
            });
        }

        $query->with(['AttributesValues' => function ($query) use ($category, $isFiltered, $attribute_top, $attributeValue) {
            $query->select('id', 'attributes_id', 'name', 'slug')
                ->whereHas('map_attributes_value_to_categories', function ($q) use ($category) {
                    $q->where('category_id', $category->id);
                })
                ->withCount(['productAttributesValues' => function ($q) use ($category, $isFiltered, $attribute_top, $attributeValue) {
                    $q->whereHas('product', function ($q2) use ($category, $isFiltered, $attribute_top, $attributeValue) {
                        $q2->where('category_id', $category->id);
                        if ($isFiltered) {
                            $q2->whereHas('productAttributesValues.attributeValue', function ($q3) use ($attribute_top, $attributeValue) {
                                $q3->where('slug', $attributeValue->slug)
                                    ->whereHas('attribute', function ($q4) use ($attribute_top) {
                                        $q4->where('slug', $attribute_top->slug);
                                    });
                            });
                        }
                    });
                }])
                ->when($isFiltered, function ($q) {
                    $q->having('product_attributes_values_count', '>', 0);
                })
                ->orderBy('name');
        }])
        ->orderBy('title');

        return $query->get()
            ->when($isFiltered, function ($collection) {
                return $collection->filter(fn($attribute) => $attribute->AttributesValues->isNotEmpty());
            })
            ->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'title' => $attribute->title,
                    'slug' => $attribute->slug,
                    'values' => $attribute->AttributesValues->map(fn($value) => [
                        'id' => $value->id,
                        'name' => $value->name,
                        'slug' => $value->slug
                    ])
                ];
            })
            ->values();
    }

    private function getTagFilterList(Tag $tag)
    {
        return Attribute::select('id', 'title', 'slug')
            ->whereHas('AttributesValues.productAttributesValues.product', function ($q) use ($tag) {
                $q->where('product_status', 1)
                ->whereHas('tags', function ($q2) use ($tag) {
                    $q2->where('tags.id', $tag->id);
                });
            })
            ->with(['AttributesValues' => function ($query) use ($tag) {
                $query->select('id', 'attributes_id', 'name', 'slug')
                    ->withCount(['productAttributesValues' => function ($q) use ($tag) {
                        $q->whereHas('product', function ($q2) use ($tag) {
                            $q2->where('product_status', 1)
                            ->whereHas('tags', function ($q3) use ($tag) {
                                $q3->where('tags.id', $tag->id);
                            });
                        });
                    }])
                    ->having('product_attributes_values_count', '>', 0)
                    ->orderBy('name');
            }])
            ->orderBy('title')
            ->get()
            ->filter(fn($attribute) => $attribute->AttributesValues->isNotEmpty())
            ->map(fn($attribute) => [
                'id' => $attribute->id,
                'title' => $attribute->title,
                'slug' => $attribute->slug,
                'values' => $attribute->AttributesValues->map(fn($value) => [
                    'id' => $value->id,
                    'name' => $value->name,
                    'slug' => $value->slug
                ])
            ])
            ->values();
    }

    
    private function getLabelFilterList(Label $label)
    {
        return Attribute::select('id', 'title', 'slug')
            ->whereHas('AttributesValues.productAttributesValues.product', function ($q) use ($label) {
                $q->where('product_status', 1)
                  ->where('label_id', $label->id);
            })
            ->with(['AttributesValues' => function ($query) use ($label) {
                $query->select('id', 'attributes_id', 'name', 'slug')
                    ->withCount(['productAttributesValues' => function ($q) use ($label) {
                        $q->whereHas('product', function ($q2) use ($label) {
                            $q2->where('product_status', 1)
                               ->where('label_id', $label->id);
                        });
                    }])
                    ->having('product_attributes_values_count', '>', 0)
                    ->orderBy('name');
            }])
            ->orderBy('title')
            ->get()
            ->filter(fn($attribute) => $attribute->AttributesValues->isNotEmpty())
            ->map(fn($attribute) => [
                'id' => $attribute->id,
                'title' => $attribute->title,
                'slug' => $attribute->slug,
                'values' => $attribute->AttributesValues->map(fn($value) => [
                    'id' => $value->id,
                    'name' => $value->name,
                    'slug' => $value->slug
                ])
            ])
            ->values();
    }

    private function notFoundResponse(string $message)
    {
        return response()->json([
            'success' => false,
            'error_code' => 'NOT_FOUND',
            'message' => $message
        ], 404);
    }

    private function invalidRequestResponse(string $message)
    {
        return response()->json([
            'success' => false,
            'error_code' => 'INVALID_REQUEST',
            'message' => $message
        ], 400);
    }

    private function serverErrorResponse(\Throwable $e, string $userMessage)
    {
        Log::error($userMessage . ' | ' . $e->getMessage(), [
            'exception' => $e,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        $payload = [
            'success' => false,
            'error_code' => 'SERVER_ERROR',
            'message' => $userMessage,
        ];

        if (config('app.debug')) {
            $payload['debug'] = [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        return response()->json($payload, 500);
    }
    /**New implement */

    
}
