<?php

namespace App\Helpers;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;

class ImageHelper
{
    protected static $manager = null;
    protected static function getManager()
    {
        if (self::$manager === null) {
            self::$manager = new ImageManager(new Driver());
        }
        return self::$manager;
    }

    /**
     * Generate unique image filename
     * 
     * @param string $name
     * @param string $prefix
     * @return string
     */
    public static function generateFileName($name, $prefix = 'nova-sac')
    {
        date_default_timezone_set('Asia/Kolkata');
        $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', strtolower($name)));
        $cleanName = $cleanName ?: 'image';
        $timestamp = round(microtime(true) * 1000);
        return $prefix.'-'.$cleanName.'-'.$timestamp;
    }
    /**
     * Get directory paths for different image sizes
     * 
     * @param string $folder
     * @return array
     */
    public static function getImageDirectories($folder = 'category')
    {
        $basePath = storage_path('app/public/images/' . $folder . '/');        
        return [
            'large' => $basePath . 'large/',
            'small' => $basePath . 'small/',
            'thumb' => $basePath . 'thumb/',
            'icon' => $basePath . 'icon/',
            'original' => $basePath . 'original/',
        ];
    }

    /**
     * Create directories if they don't exist
     * 
     * @param array $directories
     * @return void
     */
    public static function createDirectories($directories)
    {
        foreach ($directories as $path) {
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }
    /**
     * Upload and process image with different sizes
     * 
     * @param UploadedFile $image
     * @param string $name
     * @param string $folder
     * @param string|null $oldImage
     * @return string
     */
    public static function uploadImage($image, $name, $folder = 'images', $oldImage = null)
    {
        $fileName = $name.'.webp';
        $directories = self::getImageDirectories($folder);
        self::createDirectories($directories);
        if ($oldImage) {
            self::deleteImage($oldImage, $folder);
        }        
        $manager = self::getManager();
        $imagePath = $image->getRealPath();        
        /* LARGE IMAGE (1920x1080) - Maintain aspect ratio */
        $manager->read($imagePath)
            ->scale(width: 1920, height: 1080)
            ->toWebp(90)
            ->save($directories['large'] . $fileName);        
        /* SMALL IMAGE (800x600) - Maintain aspect ratio */
        $manager->read($imagePath)
            ->scale(width: 800, height: 600)
            ->toWebp(90)
            ->save($directories['small'] . $fileName);        
        /* THUMB IMAGE (150x150) - Crop to fit */
        $manager->read($imagePath)
            ->cover(150, 150)
            ->toWebp(90)
            ->save($directories['thumb'] . $fileName);        
        /* ICON IMAGE (150x150) - Crop to fit */
        $manager->read($imagePath)
            ->cover(150, 150)
            ->toWebp(90)
            ->save($directories['icon'] . $fileName);        
        /* ORIGINAL IMAGE (WebP format) */
        $manager->read($imagePath)
            ->toWebp(90)
            ->save($directories['original'] . $fileName);        
        return $fileName;
    }

    public static function uploadSingleImageWebpOnly($image_file, $name, $folder = 'simple', $oldImage = null)
    {
        $fileName = $name . '.webp';
        $basePath = storage_path("app/public/images/{$folder}/");
        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }
        if (!empty($oldImage)) {
            $oldPath = $basePath . $oldImage;
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }
        $manager = self::getManager();
        $manager->read($image_file->getRealPath())
            ->toWebp(90) 
            ->save($basePath . $fileName);
        return $fileName;
    }
    /**
     * Delete image from all directories
     * 
     * @param string $imageName
     * @param string $folder
     * @return bool
     */
    public static function deleteImage($imageName, $folder = 'category')
    {
        if (empty($imageName)) {
            return false;
        }        
        $directories = self::getImageDirectories($folder);
        $deleted = false;        
        foreach ($directories as $path) {
            $imagePath = $path . $imageName;
            if (File::exists($imagePath)) {
                File::delete($imagePath);
                $deleted = true;
            }
        }        
        return $deleted;
    }

    public static function uploadProductImageJpg(
        $image,
        $name,
        $sizeFolder = 'thumb',
        $width = 250,
        $height = 250,
        $oldImage = null,
        $prefix = 'nova-sac'
    ) {

        $fileName = $name.'.jpg';
        $dir = storage_path("app/public/images/product/jpg-image/{$sizeFolder}/");
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        if ($oldImage) {
            $oldPath = $dir.$oldImage;
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }
        $manager = self::getManager();
        $manager->read($image->getRealPath())
            ->scale(width: $width, height: $height)
            ->toJpeg(90)
            ->save($dir.$fileName);

        return $fileName;
    }

    public static function deleteProductJpgImage($imageName, $sizeFolder = 'thumb')
    {
        if (empty($imageName)) {
            return false;
        }
        $path = storage_path("app/public/images/product/jpg-image/{$sizeFolder}/".$imageName);
        if (File::exists($path)) {
            return File::delete($path);
        }
        return false;
    }

    public static function deleteSingleImage($imageName, $sizeFolder = 'storage')
    {
        if (empty($imageName)) {
            return false;
        }
        $path = storage_path("app/public/images/{$sizeFolder}/".$imageName);
        if (File::exists($path)) {
            return File::delete($path);
        }
        return false;
    }
    
    

}