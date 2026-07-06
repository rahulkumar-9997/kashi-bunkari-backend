<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\PrimaryCategory;


class PrimaryCategoryController extends Controller
{
    public function index()
    {
        $primaryCategory = PrimaryCategory::select('title', 'slug', 'id', 'primary_category_description', 'status', 'link', 'primary_category_description', 'additional_slug')
            ->latest()
            ->paginate(20);
        return view('backend.pages.primary-category.index', compact('primaryCategory'));
    }

    public function create()
    {
        return view('backend.pages.primary-category.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'short_heading_name' =>'required|string|max:1000',
            'page_url' => 'required|url|max:255',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'content' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $baseSlug = Str::slug($request->title);
            $slug = $baseSlug;
            $count = 1;
            while (PrimaryCategory::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count++;
            }
            $additional_slug = $this->generateSlugFromUrl($request->page_url);
            $primaryCategory = PrimaryCategory::create([
                'title' => $request->title,
                'short_heading' => $request->short_heading_name,
                'slug' => $slug,
                'additional_slug' => $additional_slug,
                'link' => $request->page_url,
                'primary_category_description' => $request->content,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'status' => 1
            ]);
            /*
                // Pivot save (agar use karna ho) 
                if ($request->has('product_ids')) 
                { 
                    $primaryCategory->products()->sync($request->product_ids);
                } 
            */
            Cache::forget('primary_categories');
            DB::commit();
            return redirect()->route('primary-category.index')->with('success', 'Primary Category created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Something went wrong! ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $primaryCategory = PrimaryCategory::findOrFail($id);
        return view('backend.pages.primary-category.edit', compact('primaryCategory'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'short_heading_name' =>'required|string|max:1000',
            'page_url' => 'required|url|max:255',
            'meta_title' => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:160',
            'content' => 'nullable|string',
            'status' => 'required|in:0,1'
        ]);
        try {
            DB::beginTransaction();
            $additional_slug = $this->generateSlugFromUrl($request->page_url);
            $primaryCategory = PrimaryCategory::findOrFail($id);
            $primaryCategory->title = $request->title;
            $primaryCategory->short_heading = $request->short_heading_name;
            $primaryCategory->additional_slug = $additional_slug;
            $primaryCategory->link = $request->page_url;
            $primaryCategory->primary_category_description = $request->content;
            $primaryCategory->meta_title = $request->meta_title;
            $primaryCategory->meta_description = $request->meta_description;
            $primaryCategory->status = $request->status;
            $primaryCategory->save();
            Cache::forget('primary_categories');
            DB::commit();
            return redirect()->route('primary-category.index')->with('success', 'Primary Category updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Something went wrong! ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $primaryCategory = PrimaryCategory::findOrFail($id);
            $primaryCategory->delete();
            Cache::forget('primary_categories');
            return redirect()->route('primary-category.index')->with('success', 'Primary Category deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong! ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $primaryCategory = PrimaryCategory::findOrFail($id);
            $primaryCategory->status = $request->status;
            $primaryCategory->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Error updating status: ' . $e->getMessage()
            ], 500);
        }
    }


    public function generateSlugFromUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        $segments = array_values(array_filter($segments, function ($segment) {
            return !in_array($segment, ['category', 'products']);
        }));
        $slug = implode('-', $segments);
        return Str::slug($slug);
    }
}
