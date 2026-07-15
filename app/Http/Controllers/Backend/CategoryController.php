<?php
namespace App\Http\Controllers\Backend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Subcategory;
use App\Models\Product;
use App\Models\MapCategoryAttributes;
use App\Models\UpdateHsnGstWithAttributes;
use App\Helpers\ImageHelper;
use App\Models\MappedCategoryToAttributesForFront;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class CategoryController extends Controller
{    
    public function index(){
        //$data['category_list'] = Category::with('attributes')->orderBy('id', 'desc')->get(); 
        $data['category_list'] = Category::withCount('products')->with('attributes')->orderBy('sort_order', 'asc')->paginate(20);
        $existingMappings = UpdateHsnGstWithAttributes::select('category_id', 'attributes_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category_id' => $item->category_id,
                    'attributes_id' => $item->attributes_id
                ];
            })
            ->toArray();
        $data['existing_mappings'] = $existingMappings;
        //return response()->json($data['category_list']);
        return view('backend.pages.category.index', compact('data'));
    }

    public function create(Request $request){
        $token = $request->input('_token'); 
        $size = $request->input('size'); 
        $url = $request->input('url'); 
        $attributes = Attribute::all();
        $form ='
        <div class="modal-body">
            <form method="POST" action="'.route('category.store').'" accept-charset="UTF-8" enctype="multipart/form-data" id="uploadForm">
                '.csrf_field().'
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name *</label>
                            <input type="text" id="name" name="name" class="form-control" required="">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">HSN Code</label>
                            <input type="text" id="hsn_code" name="hsn_code" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-2">
                            <label for="name" class="form-label">Map Category Of More Attributes *</label>
                            <select class="js-example-basic-multiple" name="map_category_attributes[]" id="select-attributes" multiple="multiple" required="">
                                <option value="" disabled>Select Attributes</option>';
                                    foreach ($attributes as $attribute) {
                                        $form .= '<option value="' . $attribute->id . '">' . $attribute->title . '</option>';
                                    }
                                    $form .= '
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_heading" class="form-label">Category Heading</label>
                             <input class="form-control" type="text"  id="category_heading" name="category_heading">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Category Description</label>
                            <textarea class="form-control" id="category_description" rows="2" name="description"></textarea>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="image" class="form-label">Category Image *</label>
                            <input type="file" id="image" name="image" class="form-control" required="">
                        </div>
                    </div>
                    
                    <div class="mb-3 col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="status" name="status">
                            <label class="form-check-label" for="status">Status</label>
                        </div>
                    </div>
                    <div class="mb-3 col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="trending" name="trending">
                            <label class="form-check-label" for="trending">Trending</label>
                        </div>
                    </div>
                    <div class="modal-footer pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </form>
        </div>
        ';
        return response()->json([
            'message' => 'Category Form created successfully',
            'form' => $form,
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255|unique:category,title',
        ]);
        $input['title'] = $request->input('name');
        $input['hsn_code'] = $request->input('hsn_code');
        $input['description'] = $request->input('description');
        $input['category_heading'] = $request->input('category_heading');
        if(!empty($request->input('status'))){
            $input['status'] = $request->input('status');
        }else{
            $input['status'] = 'on';
        }
        if(!empty($request->input('trending'))){
            $input['trending'] = $request->input('trending');
        }else{
            $input['trending'] = 'off';
        }        
        if ($request->hasFile('image')) {
            $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->input('name'))); 
            $baseName = ImageHelper::generateFileName($sanitized_title);
            $input['image'] = ImageHelper::uploadImage(
                $request->file('image'),
                $baseName,
                'category'
            );
            Log::info('Image uploaded: ' . $input['image']);
        }

        $category_create = Category::create($input);
        if ($request->has('map_category_attributes')) {
            foreach ($request->map_category_attributes as $attributeId) {
                MapCategoryAttributes::create([
                    'category_id' => $category_create->id,
                    'attribute_id' => $attributeId,
                ]);
            }
        }
        if($category_create){
            return redirect('category')->with('success','Category created successfully');
        }else{
             return redirect()->back()->with('error','Somthings went wrong please try again !.');
        }
    }

    public function edit(Request $request, $id){
        $attributes = Attribute::all();
        $token = $request->input('_token'); 
        $size = $request->input('size'); 
        $url = $request->input('url'); 
        $category_image = '';
        $category_status = '';
        $is_tr_status = '';
        $category_row = Category::with('attributes')->findOrFail($id);
        $category_status = ($category_row->status === 'on') ? 'checked' : '';
        $is_tr_status = ($category_row->trending === 'on') ? 'checked' : '';
        if (!empty($category_row->image)) {
            $category_image = '
            <div class="col-md-6">
                <div class="mb-3">
                    <img src="'. asset('storage/images/category/thumb/' . $category_row->image) . '" style="width: 100px;">
                </div>
            </div>
            ';
        }
        $form = '
        <div class="modal-body">
            <form method="POST" action="'.route('category.update', $category_row->id).'" accept-charset="UTF-8" enctype="multipart/form-data">
                '.csrf_field().'
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name *</label>
                            <input type="text" id="name" value="'.$category_row->title.'" name="name" class="form-control" required="">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="hsn_code" class="form-label">HSN Code</label>
                            <input type="text" id="hsn_code" name="hsn_code" value="'.$category_row->hsn_code.'" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-2">
                            <label for="name" class="form-label">Map Category Of More Attributes</label>
                            <select class="js-example-basic-multiple" name="map_category_attributes[]" id="select-attributes" multiple="multiple" required="">
                                <option value="" disabled>Select Attributes</option>';
                                    foreach ($attributes as $attribute) {
                                        $selected = $category_row->attributes->contains($attribute->id) ? 'selected' : '';
                                        $form .= '<option value="' . $attribute->id . '" ' . $selected . '>' . $attribute->title . '</option>';
                                    }
                                    $form .= '
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_heading" class="form-label">Category Heading</label>
                            <input class="form-control" type="text"  id="category_heading" name="category_heading" value="'.$category_row->category_heading.'">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Category Description</label>
                            <textarea class="form-control" id="category_description" rows="4" name="description">'.$category_row->description.'</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="image" class="form-label">Category Image</label>
                            <input type="file" id="image" name="image" class="form-control">
                        </div>
                    </div>
                    '.$category_image.'
                    <div class="mb-3 col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" '.$category_status.' type="checkbox" role="switch" id="status" name="status">
                            <label class="form-check-label" for="status">Status</label>
                        </div>
                    </div>
                    <div class="mb-3 col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" '.$is_tr_status.' type="checkbox" role="switch" id="trending" name="trending">
                            <label class="form-check-label" for="trending">Trending</label>
                        </div>
                    </div>
                    <div class="modal-footer pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </form>
        </div>';

        return response()->json([
            'message' => 'Category Form created successfully',
            'form' => $form,
        ]);

    }

    public function updateCategory(Request $request, $id){
        $request->validate([
            'name' => 'required|string|max:255|unique:category,title,' . $id,
            //'map_category_attributes' => 'array',
            //'map_category_attributes.*' => 'integer|exists:attributes,id',
        ]);
        $category_row = Category::find($id);
        $input['title'] = $request->input('name');
        $input['category_heading'] = $request->input('category_heading');
        $input['hsn_code'] = $request->input('hsn_code');
        if(!empty($request->input('status'))){
            $input['status'] = $request->input('status');
           
        }else{
            $input['status'] = 'off';
        }

        if(!empty($request->input('trending'))){
            $input['trending'] = $request->input('trending');
        }else{
            $input['trending'] = 'off';
        }
        $input['description'] = $request->input('description');
        /** First delete all map_category_attributes from this category id*/
        MapCategoryAttributes::where('category_id', $id)->delete();
        /*Then, insert the new attribute IDs into the map_category_attributes table*/
        if ($request->has('map_category_attributes')) {
            foreach ($request->map_category_attributes as $attributeId) {
                MapCategoryAttributes::create([
                    'category_id' => $id,
                    'attribute_id' => $attributeId,
                ]);
            }
        }
        if ($request->hasFile('image')) {
            $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $category_row->title)); 
            $baseName = ImageHelper::generateFileName($sanitized_title);
            $input['image'] = ImageHelper::uploadImage(
                $request->file('image'),
                $baseName,
                'category',
                $category_row->image
            );
        }
        $category_row_update = $category_row->update($input);
        if($category_row_update){
            return redirect('category')->with('success','Category updated successfully');
        }else{
             return redirect()->back()->with('error','Somthings went wrong please try again !.');
        }
    }

    public function show($id){
        // $data['category_show'] = Category::with(['attributesWithMappedValues' => function ($query) {
        //     $query->with('AttributesValues');
        // }])->where('id', $id)->first();
        $data['category_show'] = Category::where('id', $id)
        ->with([
            'attributes' => function ($query) use ($id) {
                $query->whereHas('mappedValuesForCategory', function ($mappedQuery) use ($id) {
                    $mappedQuery->where('category_id', $id);
                })->with([
                    'AttributesValues' => function ($valueQuery) use ($id) {
                        $valueQuery->whereHas('map_attributes_value_to_categories', function ($mapQuery) use ($id) {
                            $mapQuery->where('category_id', $id);
                        });
                    }
                ]);
            }
        ])->first();
        $data['mapped_attributes'] = MappedCategoryToAttributesForFront::where('category_id', $data['category_show']->id)->pluck('attributes_id')->toArray();
        //return response()->json($data['category_show']);
        return view('backend.pages.category.show', compact('data'));
    }

    public function saveMappedCategoryAttributes(Request $request){
        $request->validate([
            'category_id' => 'required|exists:category,id',
            'attributes' => 'nullable|array',
            'attributes.*' => 'exists:attributes,id'
        ]);
        DB::beginTransaction();

        try {
            Cache::forget('mega_menu_data');
            $categoryId = $request->input('category_id');
            $selectedAttributes = $request->input('attributes', []); 
            MappedCategoryToAttributesForFront::where('category_id', $categoryId)
            ->whereNotIn('attributes_id', $selectedAttributes)
            ->delete();
            if ($request->has('attributes') && !empty($request->input('attributes'))) {
                foreach ($request->input('attributes') as $attributeId) {
                    $exists = MappedCategoryToAttributesForFront::where('category_id', $request->input('category_id'))
                        ->where('attributes_id', $attributeId)
                        ->exists();
                    if (!$exists) {
                        MappedCategoryToAttributesForFront::create([
                            'category_id' => $request->input('category_id'),
                            'attributes_id' => $attributeId,
                            'sort_order' => 1,
                            'status' => 1,
                        ]);
                    }
                }
                DB::commit();
                return redirect()->back()->with('success', 'Mapped attributes to category for front saved successfully !');
            } else {
                DB::commit(); 
                return redirect()->back()->with('error', 'No attributes selected.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function deletaCategory($id)
    {
        DB::beginTransaction();
        try {

            $category = Category::with([
                'mapCategoryAttributes',
                'subCategories',
                'products'
            ])->findOrFail($id);
            MapCategoryAttributes::where('category_id', $category->id)->delete();
            Subcategory::where('category_id', $category->id)->delete();
            Product::where('category_id', $category->id)->delete();
            ImageHelper::deleteImage(
                $category->image,
                'category',
            );
            $category->delete();
            Cache::forget('mega_menu_data');
            DB::commit();
            return redirect('category')->with('success', 'Category and all related data deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Category deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function updateSort(Request $request)
    {
        try {
            DB::beginTransaction();            
            $current = Category::findOrFail($request->id);
            $target = null;            
            if ($request->direction == 'up') {
                $target = Category::where('sort_order', $current->sort_order - 1)
                    ->lockForUpdate() 
                    ->first();
                    
                if ($target) {
                    $target->update(['sort_order' => $current->sort_order]);
                    $current->decrement('sort_order');
                }
            } else {
                $target = Category::where('sort_order', $current->sort_order + 1)
                    ->lockForUpdate() 
                    ->first();
                    
                if ($target) {
                    $target->update(['sort_order' => $current->sort_order]);
                    $current->increment('sort_order');
                }
            }            
            DB::commit();
            Cache::forget('mega_menu_data');
            $directionText = $request->direction == 'up' ? 'upward' : 'downward';
            return back()->with('success', "Category moved {$directionText} successfully");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Sorting failed: ' . $e->getMessage()); 
        }
    } 
}
