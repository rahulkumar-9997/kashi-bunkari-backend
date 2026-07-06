<?php
namespace App\Http\Controllers\Backend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Helpers\ImageHelper;
class BrandController extends Controller
{
    public function index(){
        $data['brand_list'] = Brand::orderBy('id','DESC')->get(); 
        return view('backend.pages.brand.index', compact('data'));
    }

    public function create(Request $request){
        $token = $request->input('_token'); 
        $size = $request->input('size'); 
        $url = $request->input('url'); 
        $add_brand_form ='
        <div class="modal-body">
            <form method="POST" action="'.route('brand.store').'" accept-charset="UTF-8" enctype="multipart/form-data" id="uploadForm">
                '.csrf_field().'
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" id="name" name="name" class="form-control" required="">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="image" class="form-label">Image</label>
                            <input type="file" id="image" name="image" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3 col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="status" value="0">
                            <input class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="status"
                                name="status"
                                value="1">

                            <label class="form-check-label" for="status">Status</label>
                        </div>
                    </div>
                    <div class="mb-3 col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_popular" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_popular" name="is_popular">
                            <label class="form-check-label" for="is_popular">Is Popular ?</label>
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
            'message' => 'Brand Form created successfully',
            'form' => $add_brand_form,
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $input['title'] = $request->input('name');
        $input['status']     = $request->input('status', 0);
        $input['is_popular'] = $request->input('is_popular', 0);      
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->input('name')));
            $baseName = ImageHelper::generateFileName($sanitized_title);
            $image_file_name_webp = ImageHelper::uploadImage(
                $image,
                $baseName,
                'brand',
                null
            );
            $input['image'] = $image_file_name_webp;
        }
        $brand_create = Brand::create($input);
        if($brand_create){
            return redirect('brand')->with('success','Brand created successfully');
        }else{
             return redirect()->back()->with('error','Somthings went wrong please try again !.');
        }
    }

    public function updateStatus(Request $request)
    {
        $brandId = $request->input('brand_id');
        try {
            $brand = Brand::findOrFail($brandId);
            if ($request->has('popular_action')) {
                $brand->is_popular = (int) $request->input('is_popular', 0);
                $brand->save();
                return response()->json([
                    'message' => 'Is popular status updated successfully',
                    'status'  => true
                ]);
            } else {
                $brand->status = (int) $request->input('status', 0);
                $brand->save();
                return response()->json([
                    'message' => 'Brand status updated successfully',
                    'status'  => true
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Somthings went wrong please try again !.',
                'status'  => false
            ], 500);
        }
    }

    public function edit(Request $request){
        $token = $request->input('_token'); 
        $size = $request->input('size'); 
        $url = $request->input('url'); 
        $brand_id = $request->input('brand_id'); 
        $brand_row = Brand::find($brand_id);
        $brand_status ='';
        $is_popular_status ='';
        $brand_image ='';
        $brand_status      = $brand_row->status == 1 ? 'checked' : '';
        $is_popular_status = $brand_row->is_popular == 1 ? 'checked' : '';

        if (!empty($brand_row->image)) {
            $brand_image = '
            <div class="col-md-6">
                <div class="mb-3">
                    <img src="'. asset('storage/images/brand/thumb/' . $brand_row->image) . '" style="width: 100px;">
                </div>
            </div>
            ';
        }
        //dd($brand);
        $add_brand_form = '
        <div class="modal-body">
            <form method="POST" action="' . route('brand.update', $brand_row->id) . '" accept-charset="UTF-8" enctype="multipart/form-data" id="uploadForm">
                ' . csrf_field() . '
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" value="' . $brand_row->title . '" id="name" name="name" class="form-control" required="">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="image" class="form-label">Image</label>
                            <input type="file" id="image" name="image" class="form-control">
                        </div>
                    </div>
                    ' . $brand_image . '
                    <div class="mb-3 col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="status" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" value="1" ' . $brand_status . '>
                            <label class="form-check-label" for="status">Status</label>
                        </div>
                    </div>
                    <div class="mb-3 col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_popular" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_popular" name="is_popular" value="1" ' . $is_popular_status . '>
                            <label class="form-check-label" for="is_popular">Is Popular ?</label>
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
            'message' => 'Brand Form created successfully',
            'form' => $add_brand_form,
            'form2' => $brand_row,
        ]);
    }

    public function updateBrand(Request $request, $id){
        $brand_row = Brand::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $input['title'] = $request->input('name');
        $input['status']     = $request->input('status', 0);
        $input['is_popular'] = $request->input('is_popular', 0);
        
        if ($request->hasFile('image')) {
            $image = $request->file('image');
        
            $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->input('name')));
            
            $baseName = ImageHelper::generateFileName($sanitized_title);
            $image_file_name = ImageHelper::uploadImage(
                $image,
                $baseName,
                'brand',
                $brand_row->image
            );
            $input['image'] = $image_file_name;
        }
        $brand_row_update = $brand_row->update($input);
        if($brand_row_update){
            return redirect('brand')->with('success','Brand updated successfully');
        }else{
             return redirect()->back()->with('error','Somthings went wrong please try again !.');
        }
    }

    public function deleteBrand(Request $request, $id){
        $brand_row = Brand::findOrFail($id);
        $image_file_name_webp = ImageHelper::deleteImage(
            $brand_row->image,
            'brand',
        );
        $brand_row->delete();
        return redirect('brand')->with('success','Brand deleted successfully');
    }
}
