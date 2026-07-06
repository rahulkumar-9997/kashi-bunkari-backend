<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;
class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::latest()->get();
        return view('backend.pages.product.tags.index', compact('tags'));
    }

    public function create()
    {
        return view('backend.pages.product.tags.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:tags,title',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'content' => 'nullable|string',
        ]);
        DB::beginTransaction();
        $imagePath = null;
        try {
            if ($request->hasFile('image')) {
                $timestamp = round(microtime(true) * 1000);
                $sanitizedName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title));
                $baseName = ImageHelper::generateFileName($sanitizedName . '-' . $timestamp);
                $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('image'),
                    $baseName,
                    'tags'
                );
            }
            Tag::create([
                'title'             => $request->title,
                'slug'              => Tag::generateUniqueSlug($request->title),
                'image'             => $imagePath,
                'meta_title'        => $request->meta_title,
                'meta_description'  => $request->meta_description,
                'content'           => $request->content,
                'status'            => $request->boolean('status'),
            ]);
            DB::commit();
            return redirect()->route('tags.index')->with('success', 'Tag created successfully');

        } catch (\Throwable $e) {
            DB::rollBack(); 
            if (!empty($imagePath)) {
                ImageHelper::deleteSingleImage($imagePath, 'tags');
            }
            return redirect()->back()->withInput()->with('error', 'Something went wrong. ' . $e->getMessage());
        }
    }

    public function edit(Tag $tag)
    {
        return view('backend.pages.product.tags.create', compact('tag'));
    }

    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:tags,title,' . $tag->id,
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'content' => 'nullable|string',
        ]);
        DB::beginTransaction();
        $imagePath = $tag->image;
        try {
            if ($request->hasFile('image')) {
                if (!empty($tag->image)) {
                    ImageHelper::deleteSingleImage($tag->image, 'tags');
                }
                $timestamp = round(microtime(true) * 1000);
                $sanitizedName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title));
                $baseName = ImageHelper::generateFileName($sanitizedName . '-' . $timestamp);
                $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('image'),
                    $baseName,
                    'tags'
                );
            }
            $tag->update([
                'title'             => $request->title,
                'image'             => $imagePath,
                'meta_title'        => $request->meta_title,
                'meta_description'  => $request->meta_description,
                'content'           => $request->content,
                'status'            => $request->boolean('status'),
            ]);
            DB::commit();
            return redirect()->route('tags.index')->with('success', 'Tag updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Something went wrong. ' . $e->getMessage());
        }
    }

    public function destroy(Tag $tag)
    {
        DB::beginTransaction();
        try {
            if (!empty($tag->image)) {
                ImageHelper::deleteSingleImage($tag->image, 'tags');
            }
            $tag->delete();
            DB::commit();
            return redirect()
                ->route('tags.index')
                ->with('success', 'Tag deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()
                ->route('tags.index')
                ->with('error', 'Something went wrong. ' . $e->getMessage());
        }
    }
}
