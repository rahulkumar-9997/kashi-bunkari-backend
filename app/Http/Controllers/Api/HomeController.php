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
use App\Models\Tag;

use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function banner()
    {
        $banners = Cache::remember('home_banners', now()->addHours(24), function () {
            return Banner::where('status', 1)
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

	public function homeCollectionProducts(){
		$labels = Label::where('status', 1)
		->with([
			'firstProduct:id,label_id,title,slug',
			'firstProduct.firstSortedImage:id,product_id,image_path'
		])
		->select('id', 'title', 'slug')
		->take(10)
		->get()
		->map(function ($label) {
			return [
				'id' => $label->id,
				'title' => $label->title,
				'slug' => $label->slug,
				'product' => $label->firstProduct ? [
					'id' => $label->firstProduct->id,
					'title' => $label->firstProduct->title,
					'slug' => $label->firstProduct->slug,
					'image' => optional($label->firstProduct->firstSortedImage)->getSmallImages(),
				] : null,
			];
		});

		return response()->json([
			'status' => true,
			'message' => 'Shop by Collection',
			'data' => $labels,
		]);
	}

    public function newArrivals()
	{		
		$tag = Cache::remember('home_new_arrivals_tag', now()->addHours(24), function () {
			return Tag::where('slug', 'new-arrival')
				->where('status', 1)
				->first();
		});

		if (!$tag) {
			return response()->json([
				'status' => false,
				'message' => 'New Arrivals tag not found',
				'data' => []
			]);
		}

		$products = Cache::remember('home_new_arrivals_products', now()->addHours(24), function () use ($tag) {
			return Product::where('product_status', 1)
				->whereHas('tags', function ($q) use ($tag) {
					$q->where('tags.id', $tag->id);
				})
				->with([
					'category:id,title,slug',
					'firstSortedImage:id,product_id,image_path',
					'images:id,product_id,image_path,sort_order',
					'inventories' => function ($query) {
						$query->select('id', 'product_id', 'mrp', 'offer_rate', 'purchase_rate', 'sku')
							->orderBy('mrp', 'asc');
					},
					'productAttributesValues.attributeValue:id,slug'
				])
				->select('id', 'title', 'slug', 'category_id')
				->get()
				->map(function ($product) {
					$inventory = $product->inventories->first();
					$attribute_slug = optional(
						optional($product->productAttributesValues->first())->attributeValue
					)->slug;

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
						/*'images' => $product->images->map(function ($img) {
							return asset('storage/images/product/small/' . $img->image_path);
						})->values(),
						*/
					];
				})
				->shuffle()
				->take(8)
				->values();
		});

		return response()->json([
			'status' => true,
			'message' => 'New arrival products',
			'data' => $products
		]);
	}

    public function popularProducts()
	{
		$tag = Cache::remember('home_popular_products_tag', now()->addHours(24), function () {
			return Tag::where('slug', 'popular-products')
				->where('status', 1)
				->first();
		});

		if (!$tag) {
			return response()->json([
				'status' => false,
				'message' => 'Popular Tags not found',
				'data' => []
			]);
		}

		$products = Cache::remember('home_popular_products', now()->addHours(24), function () use ($tag) {
			return Product::where('product_status', 1)
				->whereHas('tags', function ($q) use ($tag) {
					$q->where('tags.id', $tag->id);
				})
				->with([
					'category:id,title,slug',
					'firstSortedImage:id,product_id,image_path',
					'images:id,product_id,image_path,sort_order',
					'inventories' => function ($query) {
						$query->select('id', 'product_id', 'mrp', 'offer_rate', 'purchase_rate', 'sku')
							->orderBy('mrp', 'asc');
					},
					'productAttributesValues.attributeValue:id,slug'
				])
				->select('id', 'title', 'slug', 'category_id')
				->get()
				->map(function ($product) {
					$inventory = $product->inventories->first();
					$attribute_slug = optional(
						optional($product->productAttributesValues->first())->attributeValue
					)->slug;

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
						/*'images' => $product->images->map(function ($img) {
							return asset('storage/images/product/small/' . $img->image_path);
						})->values(),
						*/
					];
				})
				->shuffle()
				->take(10)
				->values();
		});

		return response()->json([
			'status' => true,
			'message' => 'Popular products',
			'data' => $products
		]);
	}

	public function occasionProducts()
	{
		$tags = Tag::select('id', 'title', 'slug', 'image')
			->where('status', 1)
			->whereIn('title', [
				'Casual',
				'Office',
				'Party',
				'Festival',
				'Wedding',
				'Formal Sarees',
			])
			->withCount(['products' => function ($query) {
				$query->where('product_status', 1);
			}])
			->orderBy('title')
			->get()
			->map(function ($tag) {
				return [
					'id' => $tag->id,
					'title' => $tag->title,
					'slug' => $tag->slug,
					'image' => $tag->image
						? asset('storage/images/tags/' . $tag->image)
						: null,
					'product_count' => $tag->products_count,
				];
			});

		return response()->json([
			'status' => true,
			'message' => 'Occasion tags fetched successfully.',
			'data' => $tags,
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
        $testimonials = Cache::remember('testimonials', now()->addHours(24), function () {
            return Testimonial::select(
                    'id',
                    'name',
                    'content',
                    'designation',
                    'profile_img',
                    'city',
                    'rating'
                )
                ->where('status', 1)
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($testimonial) {
                    return [
                        'id' => $testimonial->id,
                        'name' => $testimonial->name,
                        'designation' => $testimonial->designation,
                        'city' => $testimonial->city,
                        'rating' => (int) $testimonial->rating,
                        'content' => $testimonial->content,
                        'image' => !empty($testimonial->profile_img)
                            ? asset('storage/images/testimonials/' . $testimonial->profile_img)
                            : null,
                    ];
                });
        });

        return response()->json([
            'success' => true,
            'message' => 'Testimonials retrieved successfully',
            'data' => $testimonials,
            'total' => $testimonials->count(),
        ]);
    }    
    
    public function faq()
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
