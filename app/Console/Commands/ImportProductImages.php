<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImages;
use App\Helpers\ImageHelper;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 *   php artisan products:import-images --dry-run
 *   php artisan products:import-images product_images_import
 *   php artisan products:import-images /full/absolute/path/to/folder
 *   php artisan products:import-images --skip-existing
 */
class ImportProductImages extends Command
{
    protected $signature = 'products:import-images
    {folder=product_images_import : Folder name inside storage/app, or an absolute path}
    {--dry-run : Preview matches without uploading anything}
    {--skip-existing : Skip products that already have at least one image}';

    protected $description = 'Bulk import product images from folders named after product titles';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $folderArg = $this->argument('folder');
        $basePath  = str_starts_with($folderArg, '/')
            ? $folderArg
            : storage_path('app/' . $folderArg);

        if (!is_dir($basePath)) {
            $this->error("Folder not found: {$basePath}");
            $this->line('Pass an absolute path, or place the folder at ' . storage_path('app/') . $folderArg);
            return self::FAILURE;
        }

        $dryRun       = $this->option('dry-run');
        $skipExisting = $this->option('skip-existing');
        $folders = collect(scandir($basePath))
            ->filter(fn ($f) => !in_array($f, ['.', '..']) && is_dir($basePath . '/' . $f))
            ->values();

        if ($folders->isEmpty()) {
            $this->warn("No sub-folders found inside {$basePath}");
            return self::SUCCESS;
        }

        $this->info("Found {$folders->count()} product folders in: {$basePath}");
        if ($dryRun) {
            $this->comment('DRY RUN MODE — nothing will be uploaded or saved.');
        }

        $matchedCount   = 0;
        $totalImages    = 0;
        $skippedCount   = 0;
        $unmatched      = [];

        $bar = $this->output->createProgressBar($folders->count());
        $bar->start();

        foreach ($folders as $folderName) {
            $folderPath = $basePath . '/' . $folderName;

            $product = $this->findProduct($folderName);

            if (!$product) {
                $unmatched[] = $folderName;
                $bar->advance();
                continue;
            }

            if ($skipExisting && ProductImages::where('product_id', $product->id)->exists()) {
                $skippedCount++;
                $bar->advance();
                continue;
            }

            $imageFiles = collect(glob($folderPath . '/*.{jpg,jpeg,JPG,JPEG,png,PNG,webp,WEBP}', GLOB_BRACE))
                ->sort()
                ->values();

            if ($imageFiles->isEmpty()) {
                $bar->advance();
                continue;
            }

            if ($dryRun) {
                $this->line("\n  MATCH: '{$folderName}'  ->  Product #{$product->id} \"{$product->title}\"  ({$imageFiles->count()} images)");
                $matchedCount++;
                $totalImages += $imageFiles->count();
                $bar->advance();
                continue;
            }

            try {
                $this->importImagesForProduct($product, $imageFiles);
                $matchedCount++;
                $totalImages += $imageFiles->count();
            } catch (\Exception $e) {
                $this->error("\n  FAILED for \"{$product->title}\": " . $e->getMessage());
                Log::error('Bulk product image import failed', [
                    'product_id' => $product->id,
                    'folder'     => $folderName,
                    'error'      => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Products matched: {$matchedCount}");
        $this->info("Total images processed: {$totalImages}");
        if ($skipExisting) {
            $this->info("Skipped (already had images): {$skippedCount}");
        }

        if (!empty($unmatched)) {
            $logPath = storage_path('app/unmatched_product_folders.txt');
            file_put_contents($logPath, implode("\n", $unmatched));

            $this->warn(count($unmatched) . ' folder(s) could not be matched to a product:');
            foreach (array_slice($unmatched, 0, 15) as $f) {
                $this->line("  - {$f}");
            }
            if (count($unmatched) > 15) {
                $this->line('  ... and ' . (count($unmatched) - 15) . ' more.');
            }
            $this->line("Full list saved to: {$logPath}");
        }

        return self::SUCCESS;
    }
    protected function findProduct(string $folderName): ?Product
    {
        
        $probableTitle = str_replace(['_', '-', '.'], ' ', $folderName);
        $probableTitle = trim(preg_replace('/\s+/', ' ', $probableTitle));
        // 1. Exact title match, case-insensitive
        $product = Product::whereRaw('LOWER(title) = ?', [strtolower($probableTitle)])->first();
        if ($product) {
            return $product;
        }
        // 2. Slug match built from the normalized probable title
        $product = Product::where('slug', Str::slug($probableTitle))->first();
        if ($product) {
            return $product;
        }
        // 3. Slug match built directly from the raw folder name
        $product = Product::where('slug', Str::slug($folderName))->first();
        if ($product) {
            return $product;
        }

        // 4. Loose match: strip ALL non-alphanumerics from both sides and
        $normalizedFolder = strtolower(preg_replace('/[^a-z0-9]/i', '', $folderName));
        return Product::get()->first(function ($p) use ($normalizedFolder) {
            $normalizedTitle = strtolower(preg_replace('/[^a-z0-9]/i', '', $p->title));
            return $normalizedTitle === $normalizedFolder;
        });
    }

    protected function importImagesForProduct(Product $product, $imageFiles)
    {
        $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $product->title));

        DB::beginTransaction();
        try {
            $startSort = ProductImages::where('product_id', $product->id)->max('sort_order');
            $startSort = is_null($startSort) ? 0 : $startSort + 1;

            foreach ($imageFiles as $index => $filePath) {
                $uploadedFile = new UploadedFile(
                    $filePath,
                    basename($filePath),
                    mime_content_type($filePath) ?: 'image/jpeg',
                    null,
                    true
                );

                $baseName = ImageHelper::generateFileName($sanitized_title);

                $image_file_name_webp = ImageHelper::uploadImage(
                    $uploadedFile,
                    $baseName,
                    'product',
                    null
                );

                /*ImageHelper::uploadProductImageJpg(
                    $uploadedFile,
                    $baseName,
                    'thumb',
                    250,
                    250,
                    null
                );*/

                ProductImages::create([
                    'product_id' => $product->id,
                    'image_path' => $image_file_name_webp,
                    'sort_order' => $startSort + $index,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}