<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Str;
class BlogController extends Controller
{
    public function homeBlogList()
    {
        $blogs = Cache::remember('api_home_blog_list', now()->addHours(24), function () {
            return Blog::where('status', 'published')
                ->select(
                    'id',
                    'title',
                    'slug',
                    'short_desc',
                    'content',
                    'reading_title',
                    'tags',
                    'main_image',
                    'page_image',
                    'view_count',
                    'published_at'
                )
                ->orderBy('published_at', 'desc')
                ->take(4)
                ->get()
                ->map(function ($blog) {
                    return [
                        'id' => $blog->id,
                        'title' => $blog->title,
                        'slug' => $blog->slug,
                        'view_count' => $blog->view_count,

                        'short_desc' => $blog->short_desc
                            ?? ($blog->content
                                ? Str::limit(strip_tags($blog->content), 100)
                                : null),

                        'reading_title' => $blog->reading_title,

                        'tags' => $blog->tags
                            ? array_map('trim', explode(',', $blog->tags))
                            : [],

                        'tag' => $blog->tags
                            ? trim(explode(',', $blog->tags)[0])
                            : null,

                        'main_image' => $blog->main_image
                            ? asset('storage/images/blogs/main/' . $blog->main_image)
                            : null,

                        'published_at' => $blog->published_at
                            ? Carbon::parse($blog->published_at)->format('d M Y')
                            : null,
                    ];
                });
        });

        return response()->json([
            'status' => true,
            'message' => 'Home blog list',
            'data' => $blogs,
        ]);
    }

    public function blogList()
    {
        $blogs = Cache::remember('api_blog_list', now()->addHours(24), function () {
            return Blog::where('status', 'published')
                ->select(
                    'id',
                    'title',
                    'slug',
                    'short_desc',
                    'content',
                    'reading_title',
                    'tags',
                    'main_image',
                    'page_image',
                    'view_count',
                    'published_at'
                )
                ->orderBy('published_at', 'desc')
                ->get()
                ->map(function ($blog) {
                    return [
                        'id' => $blog->id,
                        'title' => $blog->title,
                        'slug' => $blog->slug,
                        'view_count' => $blog->view_count,
                        'short_desc' => $blog->short_desc
                            ?? ($blog->content
                                ? Str::limit(strip_tags($blog->content), 100)
                                : null),
                        'reading_title' => $blog->reading_title,
                        'tags' => $blog->tags
                            ? array_map('trim', explode(',', $blog->tags))
                            : [],
                        'tag' => $blog->tags
                        ? trim(explode(',', $blog->tags)[0])
                        : null,
                        'main_image' => $blog->main_image
                            ? asset('storage/images/blogs/main/' . $blog->main_image)
                            : null,
                        'published_at' => $blog->published_at
                            ? Carbon::parse($blog->published_at)->format('d M Y')
                            : null,
                    ];
                });
        });

        return response()->json([
            'status' => true,
            'message' => 'Blog list',
            'data' => $blogs,
        ]);
    }

    public function blogDetails($slug)
    {
        $blog = Blog::where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'paragraphs' => function ($q) {
                    $q->select(
                        'id',
                        'blog_id',
                        'title',
                        'content',
                        'image',
                        'sort_order'
                    )->orderBy('sort_order', 'asc');
                },
                'images' => function ($q) {
                    $q->select(
                        'id',
                        'blog_id',
                        'image',
                        'alt_text',
                        'sort_order'
                    )->orderBy('sort_order', 'asc');
                }
            ])
            ->first();
        if (!$blog) {
            return response()->json([
                'status' => false,
                'message' => 'Blog not found',
                'data' => []
            ], 404);
        }
        $blog->increment('view_count');
        $blog->refresh();
        Cache::forget('api_blog_list');
        Cache::forget('api_home_blog_list');
        $relatedBlogs = Blog::where('status', 'published')
            ->where('id', '!=', $blog->id)
            ->select(
                'id',
                'title',
                'slug',
                'short_desc',
                'content',
                'reading_title',
				'view_count',
                'tags',
                'main_image',
                'published_at'
            )
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'short_desc' => $item->short_desc
                            ?? ($item->content
                                ? Str::limit(strip_tags($item->content), 100)
                                : null),
                    'reading_title' => $item->reading_title,
					'view_count' => $item->view_count,
                    'tag' => $item->tags
                        ? trim(explode(',', $item->tags)[0])
                        : null,
                    'main_image' => $item->main_image
                        ? asset('storage/images/blogs/main/' . $item->main_image)
                        : null,
                    'published_at' => $item->published_at
                        ? Carbon::parse($item->published_at)->format('d M Y')
                        : null,
                ];
            });
        return response()->json([
            'status' => true,
            'message' => 'Blog details',
            'data' => [
                'id' => $blog->id,
				'meta_title' => ($blog->meta_title ?: $blog->title) . ' | Kasi Bunkari',
                'meta_description' => $blog->meta_description
                    ?: $blog->short_desc
                    ?: ($blog->content
                        ? Str::limit(strip_tags($blog->content), 160)
                        : null),
                'title' => $blog->title,
                'slug' => $blog->slug,
                'reading_title' => $blog->reading_title,
                'tags' => $blog->tags
                    ? trim(explode(',', $blog->tags)[0])
                    : null,
                'view_count' => $blog->view_count,

                'short_desc' => $blog->short_desc
                    ?? null,
                'content' => $blog->content,
                'main_image' => $blog->main_image
                    ? asset('storage/images/blogs/main/' . $blog->main_image)
                    : null,
                'page_image' => $blog->page_image
                    ? asset('storage/images/blogs/page/' . $blog->page_image)
                    : null,
                'published_at' => $blog->published_at
                    ? Carbon::parse($blog->published_at)->format('d M Y')
                    : null,                
                'paragraphs' => $blog->paragraphs->map(function ($para) {
                    return [
                        'title' => $para->title,
                        'content' => $para->content,
                        'image' => $para->image
                            ? asset('storage/images/blogs/paragraphs/' . $para->image)
                            : null,
                    ];
                })->values(),
                'images' => $blog->images->map(function ($img) {
                    return [
                        'image' => $img->image
                            ? asset('storage/images/blogs/more/' . $img->image)
                            : null,
                        'alt_text' => $img->alt_text,
                    ];
                })->values(),
            ],
            'you_might_also_like' => $relatedBlogs,
        ]);
    }
}
