<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ImageHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Blog;
use App\Models\BlogParagraph;
use App\Models\BlogImage;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::with('paragraphs','images')
            ->orderBy('id', 'desc')
            ->paginate(10);
        return view('backend.pages.blogs.index', compact('blogs'));
    }

    public function create()
    {
        return view('backend.pages.blogs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'main_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'content' => 'required|string',
            'add_paragraphs' => 'nullable|boolean',
            'more_image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'paragraphs_title.*' => 'required_if:add_paragraphs,1|nullable|string|max:255',
            'paragraphs_image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'paragraphs_content.*' => 'required_if:add_paragraphs,1|nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $mainImagePath = null;
            if ($request->hasFile('main_image')) {
                $timestamp = round(microtime(true) * 1000);
                $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title));
                $baseName = ImageHelper::generateFileName($sanitized_name . '-' . $timestamp);
                $mainImagePath = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('main_image'),
                    $baseName,
                    'blogs/main'
                );
                Log::info('Blog main image uploaded: ' . $mainImagePath);
            }
            $slug = Str::slug($request->title);
            $count = Blog::where('slug', $slug)->count();
            if ($count > 0) {
                $slug = $slug . '-' . ($count + 1);
            }
            $blog = Blog::create([
                'title' => $request->title,
                'slug' => $slug,
                'short_desc' => $request->short_description,
                'content' => $request->content,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'main_image' => $mainImagePath,
                'status' => 'published',
                'published_at' => now(),
            ]);

            if ($request->hasFile('more_image')) {
                $sortOrder = 0;
                foreach ($request->file('more_image') as $image) {
                    $timestamp = round(microtime(true) * 1000) . rand(100, 999);
                    $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title));
                    $baseName = ImageHelper::generateFileName($sanitized_name . '-more-' . $timestamp);
                    $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                        $image,
                        $baseName,
                        'blogs/more'
                    );                    
                    BlogImage::create([
                        'blog_id' => $blog->id,
                        'image' => $imagePath,
                        'alt_text' => $request->title . ' image ' . ($sortOrder + 1),
                        'sort_order' => $sortOrder++
                    ]);                    
                    Log::info('Blog more image uploaded: ' . $imagePath);
                }
            }
            if ($request->has('add_paragraphs') && $request->add_paragraphs == 1) {
                if ($request->has('paragraphs_title') && is_array($request->paragraphs_title)) {
                    foreach ($request->paragraphs_title as $index => $title) {
                        if (empty($title) && empty($request->paragraphs_content[$index])) {
                            continue;
                        }
                        $paragraphData = [
                            'blog_id' => $blog->id,
                            'title' => $title,
                            'content' => $request->paragraphs_content[$index] ?? null,
                            'sort_order' => $index
                        ];
                        if ($request->hasFile('paragraphs_image.' . $index)) {
                            $image = $request->file('paragraphs_image.' . $index);
                            $timestamp = round(microtime(true) * 1000) . rand(100, 999);
                            $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $title ?: $request->title));
                            $baseName = ImageHelper::generateFileName($sanitized_name . '-para-' . $timestamp);
                            $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                                $image,
                                $baseName,
                                'blogs/paragraphs'
                            );
                            $paragraphData['image'] = $imagePath;
                            Log::info('Blog paragraph image uploaded: ' . $imagePath);
                        }
                        BlogParagraph::create($paragraphData);
                    }
                }
            }
            DB::commit();
            return redirect()->route('manage-blog.index')->with('success', 'Blog created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Blog creation failed: ' . $e->getMessage());            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create blog. Please try again. Error: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $blog = Blog::with(['paragraphs', 'images'])->findOrFail($id);
        return view('backend.pages.blogs.edit', compact('blog'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'content' => 'required|string',
            'add_paragraphs' => 'nullable|boolean',
            'more_image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'paragraphs_title.*' => 'nullable|string|max:255',
            'paragraphs_image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'paragraphs_content.*' => 'nullable|string',
            'existing_paragraphs_id.*' => 'nullable|integer|exists:blog_paragraphs,id',
            'status' => 'nullable|string|in:draft,published',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'integer|exists:blog_images,id',
            'delete_paragraphs' => 'nullable|array',
            'delete_paragraphs.*' => 'integer|exists:blog_paragraphs,id',
            'remove_main_image' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();            
            $blog = Blog::findOrFail($id);
            if ($request->hasFile('main_image')) {
                if ($blog->main_image) {
                    ImageHelper::deleteSingleImage($blog->main_image, 'blogs/main');
                }
                $timestamp = round(microtime(true) * 1000);
                $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title));
                $baseName = ImageHelper::generateFileName($sanitized_name . '-' . $timestamp);
                $mainImagePath = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('main_image'),
                    $baseName,
                    'blogs/main'
                );
                Log::info('Blog main image updated: ' . $mainImagePath);
            }             
            else {
                $mainImagePath = $blog->main_image;
            }
            
            $blog->update([
                'title' => $request->title,
                'short_desc' => $request->short_description,
                'content' => $request->content,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'main_image' => $mainImagePath,
                'status' => $request->status ?? $blog->status,
            ]);
            if ($request->has('delete_images') && is_array($request->delete_images)) {
                foreach ($request->delete_images as $imageId) {
                    $image = BlogImage::find($imageId);
                    if ($image && $image->blog_id == $blog->id) {
                        ImageHelper::deleteSingleImage($image->image, 'blogs/more');
                        $image->delete();
                        Log::info('Blog image deleted: ' . $imageId);
                    }
                }
            }
            if ($request->hasFile('more_image')) {
                $maxSortOrder = $blog->images()->max('sort_order') ?? 0;
                $sortOrder = $maxSortOrder + 1;
                foreach ($request->file('more_image') as $image) {
                    $timestamp = round(microtime(true) * 1000) . rand(100, 999);
                    $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title));
                    $baseName = ImageHelper::generateFileName($sanitized_name . '-more-' . $timestamp);
                    $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                        $image,
                        $baseName,
                        'blogs/more'
                    );
                    BlogImage::create([
                        'blog_id' => $blog->id,
                        'image' => $imagePath,
                        'alt_text' => $request->title . ' image ' . $sortOrder,
                        'sort_order' => $sortOrder++
                    ]);
                    Log::info('Blog more image uploaded during update: ' . $imagePath);
                }
            }
            
            if ($request->has('delete_paragraphs') && is_array($request->delete_paragraphs)) {
                foreach ($request->delete_paragraphs as $paragraphId) {
                    $paragraph = BlogParagraph::find($paragraphId);
                    if ($paragraph && $paragraph->blog_id == $blog->id) {
                        if ($paragraph->image) {
                            ImageHelper::deleteSingleImage($paragraph->image, 'blogs/paragraphs');
                        }
                        $paragraph->delete();
                        Log::info('Blog paragraph deleted: ' . $paragraphId);
                    }
                }
            }
            if ($request->has('add_paragraphs') && $request->add_paragraphs == 1) {
                if ($request->has('paragraphs_title') && is_array($request->paragraphs_title)) {
                    $existingParagraphIds = [];
                    foreach ($request->paragraphs_title as $index => $title) {
                        $paragraphId = isset($request->existing_paragraphs_id[$index]) ? $request->existing_paragraphs_id[$index] : null;
                        if ($paragraphId) {
                            $paragraph = BlogParagraph::where('id', $paragraphId)
                                ->where('blog_id', $blog->id)
                                ->first();
                            if ($paragraph) {
                                $existingParagraphIds[] = $paragraphId;
                                $paragraphData = [
                                    'title' => $title,
                                    'content' => $request->paragraphs_content[$index] ?? null,
                                    'sort_order' => $index
                                ];
                                if ($request->hasFile('paragraphs_image.' . $index)) {
                                    if ($paragraph->image) {
                                        ImageHelper::deleteSingleImage($paragraph->image, 'blogs/paragraphs');
                                    }
                                    $image = $request->file('paragraphs_image.' . $index);
                                    $timestamp = round(microtime(true) * 1000) . rand(100, 999);
                                    $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $title ?: $request->title));
                                    $baseName = ImageHelper::generateFileName($sanitized_name . '-para-' . $timestamp);
                                    $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                                        $image,
                                        $baseName,
                                        'blogs/paragraphs'
                                    );
                                    $paragraphData['image'] = $imagePath;
                                    Log::info('Blog paragraph image updated: ' . $imagePath);
                                }
                                $paragraph->update($paragraphData);
                            }
                        } else {
                            if (empty($title) && empty($request->paragraphs_content[$index])) {
                                continue;
                            }
                            $paragraphData = [
                                'blog_id' => $blog->id,
                                'title' => $title,
                                'content' => $request->paragraphs_content[$index] ?? null,
                                'sort_order' => $index
                            ];
                            if ($request->hasFile('paragraphs_image.' . $index)) {
                                $image = $request->file('paragraphs_image.' . $index);
                                $timestamp = round(microtime(true) * 1000) . rand(100, 999);
                                $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $title ?: $request->title));
                                $baseName = ImageHelper::generateFileName($sanitized_name . '-para-' . $timestamp);
                                $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                                    $image,
                                    $baseName,
                                    'blogs/paragraphs'
                                );
                                $paragraphData['image'] = $imagePath;
                                Log::info('New blog paragraph image uploaded during update: ' . $imagePath);
                            }
                            $newParagraph = BlogParagraph::create($paragraphData);
                            $existingParagraphIds[] = $newParagraph->id;
                        }
                    }
                    if (!empty($existingParagraphIds)) {
                        $deletedParagraphs = BlogParagraph::where('blog_id', $blog->id)
                            ->whereNotIn('id', $existingParagraphIds)
                            ->get();
                        
                        foreach ($deletedParagraphs as $paragraph) {
                            if ($paragraph->image) {
                                ImageHelper::deleteSingleImage($paragraph->image, 'blogs/paragraphs');
                            }
                            $paragraph->delete();
                        }
                    }
                }
            } else {
                foreach ($blog->paragraphs as $paragraph) {
                    if ($paragraph->image) {
                        ImageHelper::deleteSingleImage($paragraph->image, 'blogs/paragraphs');
                    }
                    $paragraph->delete();
                }
            }
            DB::commit();
            return redirect()->route('manage-blog.index')->with('success', 'Blog updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Blog update failed: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Failed to update blog. Please try again. Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();            
            $blog = Blog::findOrFail($id);
            if ($blog->main_image) {
                ImageHelper::deleteSingleImage($blog->main_image, 'blogs/main');
                Log::info('Blog main image deleted: ' . $blog->main_image);
            }
            foreach ($blog->images as $image) {
                if ($image->image) {
                    ImageHelper::deleteSingleImage($image->image, 'blogs/more');
                    Log::info('Blog more image deleted: ' . $image->image);
                }
                $image->delete();
            }
            foreach ($blog->paragraphs as $paragraph) {
                if ($paragraph->image) {
                    ImageHelper::deleteSingleImage($paragraph->image, 'blogs/paragraphs');
                    Log::info('Blog paragraph image deleted: ' . $paragraph->image);
                }
                $paragraph->delete();
            } 
            $blog->delete();            
            DB::commit();            
            return redirect()->route('manage-blog.index')->with('success', 'Blog deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Blog deletion failed: ' . $e->getMessage());            
            return redirect()->back()
                ->with('error', 'Failed to delete blog. Please try again.');
        }
    }

    

}
