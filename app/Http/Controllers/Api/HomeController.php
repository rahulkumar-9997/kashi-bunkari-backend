<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Label;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute_values;
use App\Models\Attribute;
use App\Models\Banner;
use App\Models\Client;
use App\Models\Testimonial;
use App\Models\Blog;
use App\Models\Faq;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function banner()
    {
        $banners = Cache::remember('home_banners', now()->addHours(24), function () {
            return Banner::with(['products:id,title,slug'])
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();
        });
        $banners = $banners->map(function ($banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'content' => $banner->content,
                'image_path_desktop' => $banner->image_path_desktop 
                ? asset('storage/images/banner-desktop/'.$banner->image_path_desktop)
                : null,
                'image_path_mobile' => $banner->image_path_mobile 
                ? asset('storage/images/banner-mobile/'.$banner->image_path_mobile)
                : null,
                'collection_link' => $banner->collection_link ?? null,
                'buy_now_link' => $banner->buy_now_link ?? null,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Banner list fetched successfully',
            'data' => $banners
        ]);
    }

    public function homeCategoryProducts()
    {
        $categories = Category::select('id', 'title', 'slug', 'image', 'status')
            ->where('status', 'on')
            ->withCount('products')
            ->orderBy('sort_order', 'asc') 
            ->orderBy('title', 'asc')
            ->get();

        $categories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
                'image' => $category->image 
                    ? asset('storage/images/category/large/' . $category->image)
                    : null,
                'products_count' => $category->products_count
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Category list fetched successfully',
            'data' => $categories
        ]);
    }

    public function newArrivals()
    {
        /*
        When product / label updated run this:
        Cache::forget('home_new_arrivals_products');
        Cache::forget('home_label_new_arrivals');
        Get label from cache (24 hours)
        */
        $label = Cache::remember('home_label_new_arrivals', now()->addHours(24), function () {
            return Label::where('slug', 'new-arrivals')
            ->where('status', 1)
            ->first();
        });
        if (!$label) {
            return response()->json([
                'status' => false,
                'message' => 'New Arrivals label not found',
                'data' => []
            ]);
        }
        $products = Cache::remember('home_new_arrivals_products', now()->addHours(24), function () use ($label) {
            return Product::where('product_status', 1)
                ->where('label_id', $label->id)
                ->with([
                    'category:id,title,slug',
                    'firstSortedImage:id,product_id,image_path',
                    'images:id,product_id,image_path,sort_order',
                    'inventories' => function ($query) {
                        $query->select('id','product_id','mrp','offer_rate','purchase_rate','sku')
                            ->orderBy('mrp','asc');
                    },

                    'productAttributesValues.attributeValue:id,slug'
                ])
                ->select('id','title','slug','category_id')
                ->get()
                ->map(function ($product) {
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
                        'attribute_value' => $attribute_slug,
                        'category' => [
                            'title' => $product->category->title ?? null,
                            'slug'  => $product->category->slug ?? null,
                        ],
                        'image' => $product->firstSortedImage ? $product->firstSortedImage->getSmallImages() : null,
                        'images' => $product->images->map(function ($img) {
                            return asset('storage/images/product/small/'.$img->image_path);
                        })->values(),
                    ];
                })
                ->shuffle()
                ->values();
        });

        return response()->json([
            'status' => true,
            'message' => 'New arrival products',
            'data' => $products
        ]);
    }

    public function trendingProducts()
    {
        /*
        After updating product run this code
        Cache::forget('home_trending_products');
        Cache::forget('home_label_trending_products');
        */
        $label = Cache::remember('home_label_trending_products', now()->addHours(24), function () {
            return Label::where('slug', 'trending-products')
            ->where('status', 1)
            ->first();
        });
        if (!$label) {
            return response()->json([
                'status' => false,
                'message' => 'Trending label not found',
                'data' => []
            ]);
        }
        $products = Cache::remember('home_trending_products', now()->addHours(24), function () use ($label) {
            return Product::where('product_status', 1)
                ->where('label_id', $label->id)
                ->with([
                    'category:id,title,slug',
                    'firstSortedImage:id,product_id,image_path',
                    'images:id,product_id,image_path,sort_order',
                    'inventories' => function ($query) {
                        $query->select('id','product_id','mrp','offer_rate','purchase_rate','sku')
                            ->orderBy('mrp','asc');
                    },
                    'productAttributesValues.attributeValue:id,slug'
                ])
                ->select('id','title','slug','category_id')
                ->get()
                ->map(function ($product) {
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
                        'attribute_value' => $attribute_slug,
                        'category' => [
                            'title' => $product->category->title ?? null,
                            'slug'  => $product->category->slug ?? null,
                        ],
                        'image' => $product->firstSortedImage ? $product->firstSortedImage->getSmallImages() : null,
                        'images' => $product->images->map(function ($img) {
                            return asset('storage/images/product/small/'.$img->image_path);
                        })->values(),
                    ];
                })
                ->shuffle()
                ->values();
        });
        return response()->json([
            'status' => true,
            'message' => 'Trending products',
            'data' => $products
        ]);
    }    

    public function client()
    {
        $clients = Cache::remember('home_clients', now()->addHours(24), function () {
            return Client::select('id', 'title', 'image', 'sort_order', 'status')
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'desc')
                ->limit(15)
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'title' => $client->title,
                        'image' => $client->image 
                            ? asset('storage/images/clients/'.$client->image)
                            : null,
                    ];
                });
        });

        return response()->json([
            'success' => true,
            'message' => 'Clients retrieved successfully',
            'data' => $clients,
            'total' => $clients->count()
        ]);
    }

    public function testimonials()
    {
        $testimonials = Cache::remember('home_testimonials', now()->addHours(24), function () {
            return Testimonial::select('id','name','content','designation','profile_img')
            ->where('status',1)
            ->orderBy('id','desc')
            ->limit(10)
            ->get()
            ->map(function ($testimonial) {
                return [
                    'id' => $testimonial->id,
                    'name' => $testimonial->name ?? null,
                    'designation' => $testimonial->designation ?: null,
                    'content' => $testimonial->content ?? null,
                    'image' => !empty($testimonial->profile_img)
                        ? asset('storage/images/testimonials/'.$testimonial->profile_img)
                        : null,
                ];

            });
        });
        return response()->json([
            'success' => true,
            'message' => 'Testimonials retrieved successfully',
            'data' => $testimonials,
            'total' => $testimonials->count()
        ]);
    }    
    
    
    public function homeFaq()
    {
        $faqs = Cache::remember('home_faqs', now()->addHours(24), function () {
            return Faq::select(
                    'id',
                    'question',
                    'answer',
                    'answer_image'
                )
                ->where('status', 'active')
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'desc')
                ->get();
        });

        $faqs->map(function ($faq) {
            $faq->answer_image = $faq->answer_image 
                ? asset('storage/images/faq/' . $faq->answer_image) 
                : null;
            return $faq;
        });

        return response()->json([
            'status' => true,
            'message' => $faqs->isEmpty() ? 'No FAQs found' : 'FAQ list fetched successfully',
            'data' => $faqs
        ]);
    }

}
