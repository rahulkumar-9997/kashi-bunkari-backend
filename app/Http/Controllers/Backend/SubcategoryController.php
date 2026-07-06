<?php
namespace App\Http\Controllers\Backend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Helpers\ImageHelper;
class SubcategoryController extends Controller
{
    public function index(){
        $data['subcategory_list'] = Subcategory::with('category')->get(); 
        return view('backend.pages.subcategory.index', compact('data'));
    }

    public function create(Request $request){
        $token = $request->input('_token'); 
        $size = $request->input('size'); 
        $url = $request->input('url'); 
        $category = Category::orderBy('id','DESC')->get(); 
        $form = '
        <div class="modal-body">
            <form method="POST" action="'.route('subcategory.store').'" accept-charset="UTF-8" enctype="multipart/form-data" id="uploadForm">
                '.csrf_field().'
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="name" class="form-label">Subcategory Name</label>
                            <input type="text" id="name" name="name" class="form-control" required="">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Subcategory Description</label>
                            <textarea class="form-control" id="category_description" rows="3" name="description"></textarea>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label for="category" class="form-label">Select Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Select Category</option>';
                                foreach ($category as $cat) {
                                    $form .= '<option value="'.$cat->id.'">'.$cat->title.'</option>';
                                }
                            $form .= ' 
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="image" class="form-label">Category Image</label>
                            <input type="file" id="image" name="image" class="form-control" required="">
                        </div>
                    </div>
                    
                    <div class="mb-3 col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="status" name="status">
                            <label class="form-check-label" for="status">Status</label>
                        </div>
                    </div>
                    
                    <div class="modal-footer pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
        ';

        return response()->json([
            'message' => 'Subcategory Form created successfully',
            'form' => $form,
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $input['title'] = $request->input('name');
        $input['description'] = $request->input('description');
        $input['category_id'] = $request->input('category');
        if(!empty($request->input('status'))){
            $input['status'] = $request->input('status');
        }else{
            $input['status'] = 'on';
        }  
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->input('name')));
            $baseName = ImageHelper::generateFileName($sanitized_title);
            $image_file_name_webp = ImageHelper::uploadImage(
                $image,
                $baseName,
                'subcategory',
                null
            );
            $input['image'] = $image_file_name_webp;
        }
        $subcategory_create = Subcategory::create($input);
        if($subcategory_create){
            return redirect('subcategory')->with('success','Subcategory created successfully');
        }else{
             return redirect()->back()->with('error','Somthings went wrong please try again !.');
        }
    }
    
    public function edit(Request $request){
        $token = $request->input('_token'); 
        $size = $request->input('size'); 
        $url = $request->input('url'); 
        $subcategory_id = $request->input('subcategory_id'); 
        $subcategory_row = Subcategory::find($subcategory_id);
        $category = Category::orderBy('id','DESC')->get();
        $subcategory_status ='';
        $subcategory_image ='';
        if($subcategory_row->status=='on'){
            $subcategory_status ='checked';
        }
        if (!empty($subcategory_row->image)) {
            $subcategory_image = '
            <div class="col-md-4">
                <div class="mb-3">
                    <img src="'. asset('storage/images/subcategory/thumb/' . $subcategory_row->image) . '" class="img-fluid img-thumbnail" style="width: 100px;">
                </div>
            </div>
            ';
        }
        $form = '
        <div class="modal-body">
            <form method="POST" action="'.route('subcategory.update', $subcategory_row->id).'" accept-charset="UTF-8" enctype="multipart/form-data" id="uploadForm">
                '.csrf_field().'
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="name" class="form-label">Subcategory Name</label>
                            <input type="text" id="name" name="name" value="' . $subcategory_row->title . '" class="form-control" required="">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Subcategory Description</label>
                            <textarea class="form-control" id="category_description" rows="3" name="description">'.$subcategory_row->description.'</textarea>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label for="category" class="form-label">Select Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Select Category</option>';
                                foreach ($category as $cat) {
                                    $selected = ($cat->id == $subcategory_row->category_id) ? 'selected' : '';
                                    $form .= '<option value="'.$cat->id.'" '.$selected.'>'.$cat->title.'</option>';
                                }
                            $form .= '
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="image" class="form-label">Category Image</label>
                            <input type="file" id="image" name="image" class="form-control">
                        </div>
                    </div>
                    '.$subcategory_image.'
                    
                    <div class="mb-3 col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" '.$subcategory_status.'>
                            <label class="form-check-label" for="status">Status</label>
                        </div>
                    </div>
                    
                    <div class="modal-footer pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
        ';
        return response()->json([
            'message' => 'Subcategory Form created successfully',
            'form' => $form,
        ]);
    }

    public function updateSubcategory(Request $request, $id){
        $subcategory_row = Subcategory::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $input['title'] = $request->input('name');
        $input['description'] = $request->input('description');
        $input['category_id'] = $request->input('category');
        if(!empty($request->input('status'))){
            $input['status'] = $request->input('status');
        }else{
            $input['status'] = 'on';
        }
        
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->input('name')));
            $baseName = ImageHelper::generateFileName($sanitized_title);
            $image_file_name = ImageHelper::uploadImage(
                $image,
                $baseName,
                'subcategory',
                $subcategory_row->image
            );
            $input['image'] = $image_file_name;
        }
        $subcategory_row_update = $subcategory_row->update($input);
        if($subcategory_row_update){
            return redirect('subcategory')->with('success','Subcategory updated successfully');
        }else{
             return redirect()->back()->with('error','Somthings went wrong please try again !.');
        }
    }

}
