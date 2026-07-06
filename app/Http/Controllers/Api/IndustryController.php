<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Industry;
use App\Models\IndustryCategory;

class IndustryController extends Controller
{
    public function industryCategoryList()
    {
        $industries = Cache::remember('industry_categories_list', now()->addHours(24), function () {
            return Industry::where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('title', 'asc')
            ->get()
            ->map(function ($industry) {
                return [
                    'id' =>$industry->id,
                    'title' => $industry->title,
                    'slug' => $industry->slug,
                ];
            });
        });
        return response()->json([
            'status' => true,
            'message' => 'Industry Category fetched successfully',
            'data' => $industries
        ]);
    }      

    public function industryDetails($slug)
    {
        try {
            $industryCategory = IndustryCategory::where('slug', $slug)->first(); 
            if (!$industryCategory) {
                return response()->json([
                    'status' => false,
                    'message' => 'Industry category not found'
                ], 404);
            }
            
            $industries = Cache::remember("industry_details_category_{$industryCategory->id}", now()->addHours(24), function () use ($industryCategory) {
                return Industry::with('category')
                    ->where('industry_category_id', $industryCategory->id)
                    ->where('status', 1)
                    ->orderBy('title', 'asc')
                    ->get(['id', 'title', 'slug', 'page_url', 'image_file', 'short_description', 'long_description', 'industry_category_id']); 
            });            
            
            if ($industries->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No industries found in this category'
                ], 404);
            }           
            
            $siteName = config('app.name', 'Nova SAC');            
            $industriesData = $industries->map(function ($industry) {
                return [
                    'id' => $industry->id,
                    'title' => $industry->title,
                    'slug' => $industry->slug,
                    'page_url' => $industry->page_url,
                    'image' => $industry->image_file 
                        ? asset('storage/images/industry/' . $industry->image_file)
                        : null,
                    'short_description' => $industry->short_description,
                    'long_description' => $industry->long_description,   
                    'category_name' =>$industry->category->title                
                ];
            });
            
            return response()->json([
                'status' => true,
                'data' => [
                    'category' => [
                        'id' => $industryCategory->id,
                        'title' => $industryCategory->title,
                        'slug' => $industryCategory->slug,
                    ],
                    'industries' => $industriesData,
                    'total_industries' => $industriesData->count()
                ]
            ]);
            
        } catch (\Throwable $e) {            
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching industry details'
            ], 500);
        }
    }

    
}
