<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Testimonial;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\File;
class TestimonialsController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::orderBy('id', 'desc')->paginate('10');
        return view('backend.pages.testimonial.index', compact('testimonials'));
    }

    public function create()
    {
        return view('backend.pages.testimonial.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'designation' => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:6144',
            'status' => 'nullable|boolean'
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        DB::beginTransaction();
        try {
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $count = 1;
            while (Testimonial::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }
            $imagePath = null;
            if ($request->hasFile('profile_image')) {
                $timestamp = round(microtime(true) * 1000);
                $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name));
                $baseName = ImageHelper::generateFileName($sanitized_name . '-' . $timestamp);
                $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('profile_image'),
                    $baseName,
                    'testimonials'
                );
                Log::info('Testimonial image uploaded: ' . $imagePath);
            }

            Testimonial::create([
                'name' => $request->name,
                'slug' => $slug,
                'content' => $request->content,
                'designation' => $request->designation ?? null,
                'profile_img' => $imagePath,
                'status' => $request->status ? true : false
            ]);
            Cache::forget('home_testimonials');
            DB::commit();
            return redirect('manage-testimonials')->with('success', 'Testimonial created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        return view('backend.pages.testimonial.edit', compact('testimonial'));
    }

    public function update(Request $request, $id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'designation' => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:6144',
            'status' => 'nullable|boolean'
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        DB::beginTransaction();
        try {
            $imagePath = $testimonial->profile_img;
            if ($request->hasFile('profile_image')) {
                if ($testimonial->profile_img) {
                    $deleteImageFile = ImageHelper::deleteSingleImage(
                        $testimonial->profile_img,
                        'testimonials'
                    );
                } 
                $timestamp = round(microtime(true) * 1000);
                $sanitized_name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name));
                $baseName = ImageHelper::generateFileName($sanitized_name . '-' . $timestamp);
                $imagePath = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('profile_image'),
                    $baseName,
                    'testimonials'
                );
                Log::info('Testimonial image updated: ' . $imagePath);
            }

            $testimonial->update([
                'name' => $request->name,
                'content' => $request->content,
                'designation' => $request->designation ?? null,
                'profile_img' => $imagePath,
                'status' => $request->status ? true : false
            ]);
            Cache::forget('home_testimonials');
            DB::commit();
            return redirect('manage-testimonials')->with('success', 'Testimonial updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        DB::beginTransaction();
        try {
            if ($testimonial->profile_img) {
                $deleteImageFile = ImageHelper::deleteSingleImage(
                    $testimonial->profile_img,
                    'testimonials'
                );
            }
            $testimonial->delete();
            Cache::forget('home_testimonials');
            DB::commit();
            return redirect('manage-testimonials')->with('success', 'Testimonial deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
