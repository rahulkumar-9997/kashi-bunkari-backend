<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;

class CkeditorController extends Controller
{
    public function upload(Request $request)
    {
        Log::info('CKEditor upload request', [
            'has_file' => $request->hasFile('upload'),
            'all_files' => $request->allFiles(),
            'has_token' => $request->has('_token'),
            'method' => $request->method()
        ]);
        
        if ($request->hasFile('upload')) {

            try {
                $imageFile = $request->file('upload');               
                if (!$imageFile->isValid()) {
                    throw new \Exception('Invalid file upload. Error: ' . $imageFile->getError());
                }
                if (!in_array($imageFile->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                    throw new \Exception('Invalid file type. Only images are allowed.');
                }

                $originalName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $baseName = ImageHelper::generateFileName($originalName, 'ckeditor');
                $fileName = ImageHelper::uploadSingleImageWebpOnly(
                    $imageFile,
                    $baseName,
                    'ckeditor'
                );
                $url = asset('storage/images/ckeditor/' . $fileName);
                $CKEditorFuncNum = $request->input('CKEditorFuncNum');
                if ($CKEditorFuncNum) {
                    return response(
                        "<script>
                        window.parent.CKEDITOR.tools.callFunction({$CKEditorFuncNum}, '{$url}', '');
                        </script>"
                    );
                } else {
                    return response()->json([
                        'uploaded' => 1,
                        'fileName' => $fileName,
                        'url' => $url
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::error('CKEditor upload failed: ' . $e->getMessage());
                
                $CKEditorFuncNum = $request->input('CKEditorFuncNum');
                $errorMessage = 'Upload failed: ' . $e->getMessage();
                
                if ($CKEditorFuncNum) {
                    return response(
                        "<script>
                        window.parent.CKEDITOR.tools.callFunction({$CKEditorFuncNum}, '', '{$errorMessage}');
                        </script>"
                    );
                } else {
                    return response()->json([
                        'uploaded' => 0,
                        'error' => ['message' => $errorMessage]
                    ]);
                }
            }
        }
        $CKEditorFuncNum = $request->input('CKEditorFuncNum');
        $errorMessage = 'No file uploaded.';
        
        if ($CKEditorFuncNum) {
            return response(
                "<script>
                window.parent.CKEDITOR.tools.callFunction({$CKEditorFuncNum}, '', '{$errorMessage}');
                </script>"
            );
        } else {
            return response()->json([
                'uploaded' => 0,
                'error' => ['message' => $errorMessage]
            ]);
        }
    }

    public function imageList(Request $request)
    {
        try {
            $imagePath = storage_path('app/public/images/ckeditor/');
            $images = [];
            if (!File::exists($imagePath)) {
                return response()->json(['error' => 'Directory not found'], 404);
            }
            $files = File::files($imagePath);
            
            foreach ($files as $file) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $images[] = [
                        'url' => asset('storage/images/ckeditor/' . $file->getFilename()),
                        'name' => $file->getFilename(),
                        'size' => $file->getSize(),
                        'time' => $file->getMTime(),
                        'type' => $file->getExtension()
                    ];
                }
            }
            
            usort($images, function($a, $b) {
                return $b['time'] - $a['time'];
            });
            
            return response()->json($images);
            
        } catch (\Exception $e) {
            Log::error('CKEditor image list failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load images'], 500);
        }
    }
    
    /**
     * Optional: Delete CKEditor image
     */
    public function deleteImage(Request $request)
    {
        try {
            $imageName = $request->input('image');            
            if (empty($imageName)) {
                return response()->json(['error' => 'Image name required'], 400);
            }
            if (strpos($imageName, '..') !== false || strpos($imageName, '/') !== false) {
                return response()->json(['error' => 'Invalid image name'], 400);
            }            
            Log::info('Attempting to delete image', ['image' => $imageName]);            
            $deleted = ImageHelper::deleteSingleImage($imageName, 'ckeditor');            
            if ($deleted) {
                Log::info('Image deleted successfully', ['image' => $imageName]);
                return response()->json([
                    'success' => true, 
                    'message' => 'Image deleted successfully'
                ]);
            } else {
                Log::warning('Image not found for deletion', ['image' => $imageName]);
                return response()->json([
                    'error' => 'Image not found'
                ], 404);
            }
            
        } catch (\Exception $e) {
            Log::error('CKEditor delete failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }
}