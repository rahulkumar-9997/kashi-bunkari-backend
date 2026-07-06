<?php

namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Helpers\ImageHelper;
use App\Models\ImageStorage;
use App\Models\ProductImages;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StorageController extends Controller
{   
    public function index(){
        $data['image_storage'] = ImageStorage::orderBy('id', 'DESC')->get();
        return view('backend.pages.manage-storage.index', compact('data'));
    }

    public function create(Request $request){
        $token = $request->input('_token'); 
        $size = $request->input('size'); 
        $url = $request->input('url'); 
        $form ='
            <div class="modal-body">
                <div id="error-container"></div>
                <form method="POST" action="'.route('manage-storage.submit').'" accept-charset="UTF-8" enctype="multipart/form-data" id="imageStorage">
                    '.csrf_field().'
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="product_image" class="form-label"> Select Images Multiple *</label>
                                <input type="file" id="storage_images" name="storage_images[]" class="form-control"  accept="image/*" multiple>
                            </div>
                            <div id="image-preview"></div>
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
            'message' => 'Form loaded successfully',
            'form' => $form,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreProductImageRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'storage_images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($request->hasFile('storage_images')) {
            DB::beginTransaction();
            try {
                $files = $request->file('storage_images');
                foreach ($files as $key => $file) {
                    $timestamp = round(microtime(true) * 1000);
                    $image_file_name = 'wooden-souvenir' . $timestamp;
                    $baseName = ImageHelper::generateFileName($image_file_name);
                    $webpImageFile = ImageHelper::uploadSingleImageWebpOnly(
                        $file,
                        $baseName,
                        'storage'
                    );
                    ImageStorage::create([
                        'image_storage_path' => $webpImageFile,
                    ]);
                }
                
                DB::commit();
                $data['image_storage'] = ImageStorage::orderBy('id', 'DESC')->get();
                return response()->json([
                    'status' => 'success',
                    'storageImages' => view('backend.pages.manage-storage.partials.storage-image-list', compact('data'))->render(),
                    'message' => 'Images uploaded successfully'
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Image Storage Error: ' . $e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to upload images. Please try again.'
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select at least one image file.'
            ], 400);
        }
    }

        
    public function mappedImageToProductSubmit(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'selected_images' => 'required|array',
            'selected_images.*' => 'exists:image_storage,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        try {
            DB::beginTransaction();
            $product_id = $request->product_id;
            $product = Product::findOrFail($product_id);
            $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $product->title));
            $sourcePath = storage_path('app/public/images/storage/'); 
            
            foreach ($request->selected_images as $imageId) {
                $storageImageRow = ImageStorage::findOrFail($imageId);
                $storageFullPath = $sourcePath . $storageImageRow->image_storage_path;    
                if (!File::exists($storageFullPath)) {
                    Log::warning("Image not found: " . $storageFullPath);
                    continue;
                }    
                $baseName = ImageHelper::generateFileName($sanitized_title);
                try {
                    $uploadedFile = new \Illuminate\Http\UploadedFile(
                        $storageFullPath,
                        basename($storageFullPath),
                        mime_content_type($storageFullPath),
                        null,
                        true
                    );
                    $image_file_name_webp = ImageHelper::uploadImage(
                        $uploadedFile,
                        $baseName,
                        'product',
                        null
                    );
                    $image_file_name_jpg = ImageHelper::uploadProductImageJpg(
                        $uploadedFile,
                        $baseName,
                        'thumb',
                        250, 
                        250, 
                        null,  
                    );
                    ProductImages::create([
                        'product_id' => $product_id,
                        'image_path' => $image_file_name_webp
                    ]);
                    /* Delete image from storage folder */
                    File::delete($storageFullPath);    
                    /* Delete storage row also */
                    $storageImageRow->delete();
                    
                } catch (\Exception $e) {
                    Log::error("Image processing failed: " . $e->getMessage());
                    continue;
                }
            }
    
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Images mapped successfully.']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Image Mapping Failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to map images. Try again.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $image = ImageStorage::findOrFail($id);
            $imagePath = public_path('images/storage/' . $image->image_storage_path);
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
            $deleteImageFile = ImageHelper::deleteSingleImage(
                $image->image_storage_path,
                'storage'
            );

            $image->delete();
            $data['image_storage'] = ImageStorage::orderBy('id', 'DESC')->get();
            return response()->json([
                'status' => 'success',
                'storageImages' => view('backend.pages.manage-storage.partials.storage-image-list', compact('data'))->render(),
                'message' => 'Image deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete the image.'], 500);
        }
    }

    public function storageCommentSubmit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'storage_comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Comment field required.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $image = ImageStorage::findOrFail($id);
            $image->comments = $request->input('storage_comment');
            $image->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Comment saved successfully.',
            ]);
        } catch (\Exception $e) {
            //Log::error('Error saving comment for image ID ' . $id . ': ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save comment. Please try again later.',
            ], 500);
        }
    }


    
}
