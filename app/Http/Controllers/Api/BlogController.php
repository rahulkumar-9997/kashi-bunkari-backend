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
                    'main_image',
                    'page_image',
                    'published_at'
                )
                ->orderBy('published_at','desc')
                ->get()
                ->map(function ($blog) {
                    return [
                        'id' => $blog->id,
                        'title' => $blog->title,
                        'slug' => $blog->slug,
                        'short_desc' => $blog->short_desc ?: null,
                        'content' => $blog->content ?: null,
                        'main_image' => $blog->main_image
                            ? asset('storage/images/blogs/main/'.$blog->main_image)
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
            'data' => $blogs
        ]);
    }

    public function blogDetails($slug)
    {
        $blog = Cache::remember("api_blog_$slug", now()->addHours(24), function () use ($slug) {
            return Blog::where('slug', $slug)
                ->where('status', 'published')
                ->with([
                    'paragraphs' => function ($q) {
                        $q->select('id','blog_id','title','content','image','sort_order')
                        ->orderBy('sort_order','asc');
                    },
                    'images' => function ($q) {
                        $q->select('id','blog_id','image','alt_text')
                        ->orderBy('sort_order','asc');
                    }
                ])
                ->first();
        });
        if (!$blog) {
            return response()->json([
                'status' => false,
                'message' => 'Blog not found',
                'data' => []
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Blog details',
            'data' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'short_desc' => $blog->short_desc ?? null,
                'content' => $blog->content ?? null,
                'main_image' => $blog->main_image
                    ? asset('storage/images/blogs/main/'.$blog->main_image)
                    : null,
                'published_at' => $blog->published_at
                    ? Carbon::parse($blog->published_at)->format('d M Y')
                    : null,
                'meta_title' => ($blog->meta_title ?? $blog->title).' | Nova Sac',
                'meta_description' => $blog->meta_description 
                    ?? $blog->short_desc 
                    ?? Str::limit(strip_tags($blog->content),160),               
                'paragraphs' => $blog->paragraphs->map(function ($para) {
                    return [
                        'title' => $para->title,
                        'content' => $para->content,
                        'image' => $para->image
                            ? asset('storage/images/blogs/paragraphs/'.$para->image)
                            : null
                    ];
                })->values(),
                'images' => $blog->images->map(function ($img) {
                    return [
                        'image' => asset('storage/images/blogs/more/'.$img->image),
                        'alt_text' => $img->alt_text
                    ];
                })->values()
            ]
        ]);
    }
}
