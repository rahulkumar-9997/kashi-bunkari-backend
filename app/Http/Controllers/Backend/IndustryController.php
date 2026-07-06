<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\IndustryCategory;
use App\Models\Industry;
use Illuminate\Support\Facades\Cache;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;
class IndustryController extends Controller
{

    public function industryCategoryIndex()
    {
        $industryCategory = IndustryCategory::orderBy('id', 'desc')
        ->paginate(20);
        return view('backend.pages.manage-industry.industry-category.index', compact('industryCategory'));
    }

    public function industryCategoryCreate()
    {        
        return view('backend.pages.manage-industry.industry-category.create');
    }

    public function industryCategoryStore(Request $request) {
        $request->validate([
            'title' => 'required|string|max:255|unique:industry_categories,title',
            'status' => 'nullable|boolean',
        ]);
        DB::beginTransaction();
        try {   
            $industryCategory = IndustryCategory::create([
                'title' => $request->title,
                'status' => $request->boolean('status'),
            ]);
            Cache::forget('industry_categories_list');
            DB::commit();
            return redirect()->route('industry-category.index')->with('success', 'Industry Category created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create industry category. Please try again.');
        }
    }

    public function industryCategoryEdit($id)
    {
        $industryCategory = IndustryCategory::findOrFail($id);
        return view('backend.pages.manage-industry.industry-category.edit', compact('industryCategory'));
    }

    public function industryCategoryUpdate(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:industry_categories,title,' . $id,
            'status' => 'nullable|boolean',
        ]);
        DB::beginTransaction();
        try {
            $category = IndustryCategory::findOrFail($id);            
            $category->update([
                'title' => $request->title,
                'status' => $request->boolean('status'),
            ]);
            Cache::forget('industry_categories_list');
            DB::commit();
            return redirect()->route('industry-category.index')->with('success', 'Industry Category updated successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Industry Category update failed: ' . $e->getMessage());            
            return back()->withInput()->with('error', 'Failed to update industry category. Please try again.');
        }
    }

    public function industryCategoryDelete($id)
    {
        DB::beginTransaction();
        try {
            $category = IndustryCategory::findOrFail($id);
            if ($category->industries()->exists()) {
                return back()->with('error', 'Cannot delete this category because it has associated industries. Please reassign or delete those industries first.');
            }            
            $category->delete();
            Cache::forget('industry_categories_list');
            DB::commit();
            return redirect()->route('industry-category.index')->with('success', 'Industry Category deleted successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Industry Category deletion failed: ' . $e->getMessage());            
            return back()->with('error', 'Failed to delete industry category. Please try again.');
        }
    }

    public function index()
    {

        $industries = Industry::with('category')
            ->orderBy('id', 'desc')
            ->paginate(20);
        return view('backend.pages.manage-industry.index', compact('industries'));
    }

    public function create()
    {
        $industryCategory = IndustryCategory::orderBy('id', 'desc')
        ->get();
        return view('backend.pages.manage-industry.create', compact('industryCategory'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'industry_category_id' => 'required|exists:industry_categories,id',
            'title' => 'required|string|max:255',
            'industry_image' => 'required|file|extensions:jpg,jpeg,png,webp|max:4096',
            'page_url' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'short_description' => 'nullable|string|max:1000',
            'long_description' => 'nullable|string',
            'status' => 'nullable|boolean',
        ]);
        DB::beginTransaction();
        try {
            $industry_img = null;
            if ($request->hasFile('industry_image')) {
                $timestamp = round(microtime(true) * 1000);
                $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title));
                $baseName = ImageHelper::generateFileName($sanitized_name . '-' . $timestamp);
                $industry_img = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('industry_image'),
                    $baseName,
                    'industry'
                );
            }

            $industry = Industry::create([
                'industry_category_id' => $request->industry_category_id,
                'title' => $request->title,
                'page_url' => $request->page_url,
                'image_file' => $industry_img,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'short_description' => $request->short_description,
                'long_description' => $request->long_description,
                'status' => $request->boolean('status'),
            ]);
            // if (!empty($request->products)) {
            //     $industry->products()->sync($request->products);
            // }  
            Cache::forget("industry_details_{$industry->slug}");
            Cache::forget("industry_details_category_{$industry->industry_category_id}");
            DB::commit();
            return redirect()->route('manage-industry.index')->with('success', 'Industry created successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $industryCategory = IndustryCategory::orderBy('title', 'asc')->get(); 
        $industry = Industry::with('products')->findOrFail($id);
        $selectedProducts = $industry->products->pluck('id')->toArray();
        return view('backend.pages.manage-industry.edit', compact('industry', 'selectedProducts', 'industryCategory'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'industry_category_id' => 'required|exists:industry_categories,id',
            'title' => 'required|string|max:255',
            'industry_image' => 'nullable|file|extensions:jpg,jpeg,png,webp|max:4096',
            'page_url' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'short_description' => 'nullable|string|max:1000',
            'long_description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'remove_image' => 'nullable|boolean',
        ]);        
        DB::beginTransaction();
        try {
            $industry = Industry::findOrFail($id);           
            $industry_img = $industry->image_file;
            if ($request->hasFile('industry_image')) {
                if ($industry->image_file) {
                    ImageHelper::deleteImage('industry/' . $industry->image_file);
                }                
                $timestamp = round(microtime(true) * 1000);
                $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title));
                $baseName = ImageHelper::generateFileName($sanitized_name . '-' . $timestamp);
                $industry_img = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('industry_image'),
                    $baseName,
                    'industry'
                );
            }
            $industry->update([
                'industry_category_id' => $request->industry_category_id,
                'title' => $request->title,
                'page_url' => $request->page_url,
                'image_file' => $industry_img,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'short_description' => $request->short_description,
                'long_description' => $request->long_description,
                'status' => $request->boolean('status'),
            ]); 
            Cache::forget("industry_details_{$industry->slug}");
            Cache::forget("industry_details_category_{$industry->industry_category_id}");       
            DB::commit();
            return redirect()->route('manage-industry.index')->with('success', 'Industry updated successfully');            
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update_old_with_products(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'products' => 'required|array|min:1',
            'products.*' => 'exists:products,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'short_description' => 'nullable|string|max:1000',
            'long_description' => 'nullable|string',
            'status' => 'nullable|boolean',
        ]);
        DB::beginTransaction();
        try {
            $industry = Industry::findOrFail($id);
            $industry->update([
                'title' => $request->title,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'short_description' => $request->short_description,
                'long_description' => $request->long_description,
                'status' => $request->boolean('status'),
            ]);
            if (!empty($request->products)) {
                $industry->products()->sync($request->products);
            }
            Cache::forget('industry_list_data');
            Cache::forget("industry_details_{$industry->slug}");
            DB::commit();
            return redirect()->route('manage-industry.index')->with('success', 'Industry updated successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $industry = Industry::findOrFail($id);
            if ($industry->image_file) {
                ImageHelper::deleteImage('industry/' . $industry->image_file);
            }
            Cache::forget("industry_details_{$industry->slug}");
            Cache::forget("industry_details_category_{$industry->industry_category_id}");
            if (method_exists($industry, 'products')) {
                $industry->products()->detach();
            }
            $industry->delete();            
            DB::commit();           
            return redirect()->route('manage-industry.index')->with('success', 'Industry deleted successfully');            
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }
}
