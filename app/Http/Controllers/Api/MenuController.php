<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Category;

class MenuController extends Controller
{
    public function menu()
    {
        $categories = Cache::remember('mega_menu_data', now()->addHours(24), function () {
            return Category::query()
                ->with([
                    'attributes' => function ($query) {
                        $query->whereHas('mappedCategoryToAttributesForFront')
                            ->orderBy('title');
                    },
                    'attributes.AttributesValues' => function ($query) {
                        $query->whereHas('map_attributes_value_to_categories');
                    },
                    'attributes.mappedCategoryToAttributesForFront',
                    'attributes.AttributesValues.map_attributes_value_to_categories'
                ])
                ->orderBy('sort_order', 'asc') 
                ->orderBy('title', 'asc')
                ->get()
                ->map(function ($category) {
                    $attributesWithValues = $category->attributes
                        ->filter(function ($attribute) use ($category) {
                            return $attribute->mappedCategoryToAttributesForFront
                                ->contains('category_id', $category->id);
                        })
                        ->map(function ($attribute) use ($category) {

                            $values = $attribute->AttributesValues
                                ->filter(function ($value) use ($category) {
                                    return $value->map_attributes_value_to_categories
                                        ->contains('id', $category->id);
                                })
                                ->map(function ($value) {
                                    return [
                                        'name' => $value->name,
                                        'slug' => $value->slug
                                    ];
                                })
                                ->values();

                            return $values->isNotEmpty() ? [
                                'title' => $attribute->title,
                                'slug' => $attribute->slug,
                                'values' => $values
                            ] : null;
                        })
                        ->filter()
                        ->values();
                    return $attributesWithValues->isNotEmpty() ? [
                        'title' => $category->title,
                        'category_slug' => $category->slug,
                        'category_image' => $category->image ?
                                            asset('storage/images/category/thumb/'.$category->image)
                                            : null,
                        'attributes' => $attributesWithValues
                    ] : null;
                })
                ->filter()
                ->values();
        });

        return response()->json([
            'status' => true,
            'message' => 'Menu fetched successfully',
            'data' => $categories
        ]);
    }
    
}
