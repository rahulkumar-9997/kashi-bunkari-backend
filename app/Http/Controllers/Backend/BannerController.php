<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Helpers\ImageHelper;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::with('products')
            ->orderBy('id', 'desc')
            ->get();
        return view('backend.pages.manage-banner.index', compact('banners'));
    }

    public function create()
    {
        return view('backend.pages.manage-banner.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'desktop_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:6144',
            'mobile_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:6144',
            //'products' => 'nullable|array'
            'collection_link' => 'nullable|url',
            'buy_now_link' => 'nullable|url'
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        DB::beginTransaction();
        try {
            $input = [
                'title' => $request->title,
                'content' => $request->content,
                'status' => true,
                'collection_link' => $request->collection_link,
                'buy_now_link' => $request->buy_now_link
            ];
            if ($request->hasFile('desktop_image')) {
                $timestamp = round(microtime(true) * 1000);
                $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title)) . '-' . $timestamp;
                $baseName = ImageHelper::generateFileName($sanitized_title);
                $input['image_path_desktop'] = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('desktop_image'),
                    $baseName,
                    'banner-desktop'
                );
                Log::info('Banner desktop image uploaded: ' . $input['image_path_desktop']);
            }
            if ($request->hasFile('mobile_image')) {
                $timestamp = round(microtime(true) * 1000);
                $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title)) . '-' . $timestamp;
                $baseName = ImageHelper::generateFileName($sanitized_title);
                $input['image_path_mobile'] = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('mobile_image'),
                    $baseName,
                    'banner-mobile'
                );
                Log::info('Banner mobile image uploaded: ' . $input['image_path_mobile']);
            }
            $banner = Banner::create($input);
            if ($request->products) {
                $banner->products()->sync($request->products);
            }
            Cache::forget('home_banners');
            DB::commit();
            return redirect('manage-banner')->with('success', 'Banner created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Banner store failed', [
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Something went wrong')->withInput();
        }
    }

    public function edit(Request $request, $id)
    {
        $banner = Banner::with('products')->findOrFail($id);
        $selectedProducts = $banner->products->pluck('id')->toArray();
        return view('backend.pages.manage-banner.edit', compact('banner', 'selectedProducts'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'image_path_desktop' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:6144',
            'image_path_mobile' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:6144',
            //'products' => 'nullable|array'
            'collection_link' => 'nullable|url',
            'buy_now_link' => 'nullable|url'
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        DB::beginTransaction();
        try {
            $banner = Banner::findOrFail($id);
            $input = [
                'title' => $request->title,
                'content' => $request->content,
                'collection_link' => $request->collection_link,
                'buy_now_link' => $request->buy_now_link
            ];
            if ($request->hasFile('desktop_image')) {
                /*delete image in folder */
                // $imagePath = public_path('images/banner-desktop/' . $banner->image_path_desktop);
                // if (File::exists($imagePath)) {
                //     File::delete($imagePath);
                // }
                $deleteImageFile = ImageHelper::deleteSingleImage(
                    $banner->image_path_desktop,
                    'banner-desktop'
                );
                /*delete image in folder */
                $timestamp = round(microtime(true) * 1000);
                $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title)) . '-' . $timestamp;
                $baseName = ImageHelper::generateFileName($sanitized_title);
                $input['image_path_desktop'] = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('desktop_image'),
                    $baseName,
                    'banner-desktop'
                );
                Log::info('Banner desktop image updated: ' . $input['image_path_desktop']);
            }
            if ($request->hasFile('mobile_image')) {
                /*delete image in folder */
                // $imagePathMobile = public_path('images/banner-mobile/' . $banner->image_path_mobile);
                // if (File::exists($imagePathMobile)) {
                //     File::delete($imagePathMobile);
                // }
                $deleteImageFileMobile = ImageHelper::deleteSingleImage(
                    $banner->image_path_mobile,
                    'banner-mobile'
                );
                $timestamp = round(microtime(true) * 1000);
                $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title)) . '-' . $timestamp;
                $baseName = ImageHelper::generateFileName($sanitized_title);
                $input['image_path_mobile'] = ImageHelper::uploadSingleImageWebpOnly(
                    $request->file('mobile_image'),
                    $baseName,
                    'banner-mobile'
                );
                Log::info('Banner mobile image updated: ' . $input['image_path_mobile']);
            }
            $banner->update($input);
            if ($request->products) {
                $banner->products()->sync($request->products);
            } else {
                $banner->products()->detach();
            }
            Cache::forget('home_banners');
            DB::commit();
            return redirect('manage-banner')->with('success', 'Banner updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Banner update failed', [
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $banner = Banner::findOrFail($id);
            /* Delete Desktop Image */
            ImageHelper::deleteSingleImage(
                $banner->image_path_desktop,
                'banner-desktop'
            );
            /* Delete Mobile Image */
            ImageHelper::deleteSingleImage(
                $banner->image_path_mobile,
                'banner-mobile'
            );
            $banner->products()->detach();
            $banner->delete();
            Cache::forget('home_banners');
            DB::commit();
            return redirect()->back()->with('success', 'Banner and its images deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Banner delete failed', [
                'banner_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Something went wrong while deleting banner');
        }
    }
}
