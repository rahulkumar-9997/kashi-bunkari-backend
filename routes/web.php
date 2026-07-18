<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Backend\LoginController;
use App\Http\Controllers\Backend\ForgotPasswordController;
use App\Http\Controllers\Backend\CacheController;
use App\Http\Controllers\Backend\UsersController;
use App\Http\Controllers\Backend\RolesController;
use App\Http\Controllers\Backend\PermissionsController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\CkeditorController;
use App\Http\Controllers\Backend\BrandController;
use App\Http\Controllers\Backend\LabelController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\SubcategoryController;
use App\Http\Controllers\Backend\AttributeController;
use App\Http\Controllers\Backend\ProductsController;
use App\Http\Controllers\Backend\InventoryController;
use App\Http\Controllers\Backend\OrderControllerBackend;
use App\Http\Controllers\Backend\CustomerControllerBackend;
use App\Http\Controllers\Backend\DatabaseController;
use App\Http\Controllers\Backend\BannerController;
use App\Http\Controllers\Backend\OurClientController;
use App\Http\Controllers\Backend\TestimonialsController;
use App\Http\Controllers\Backend\BlogController;
use App\Http\Controllers\Backend\FaqController;
use App\Http\Controllers\Backend\StorageController;
use App\Http\Controllers\Backend\RelatedProductController;
use App\Http\Controllers\Backend\PrimaryCategoryController;
use App\Http\Controllers\Backend\MagicAiImageGeneratorController;
use App\Http\Controllers\Backend\TagController;
Route::get('/', [LoginController::class, 'showLoginForm']);
Route::prefix('admin')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm']);
    Route::post('/login', [LoginController::class, 'login'])->name('login');
    Route::get('forget/password', [ForgotPasswordController::class, 'showForgetPasswordForm'])->name('forget.password');
    Route::post('forget.password', [ForgotPasswordController::class, 'submitForgetPasswordForm'])->name('forget.password.submit');

    Route::get('reset-password/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('reset.password.get');
    Route::post('reset-password', [ForgotPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.post');
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::group(['middleware' => ['auth:web']], function() {
    //Route::group(['middleware' => ['admin']], function () {
    Route::group(['prefix' => 'users'], function() {
        Route::get('/', [UsersController::class, 'index'])->name('users');
        Route::get('/create', [UsersController::class, 'create'])->name('users.create');
        Route::post('/create', [UsersController::class, 'store'])->name('users.store');
        Route::get('/{user}/show', [UsersController::class, 'show'])->name('users.show');
        Route::get('/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
        Route::patch('/{user}/update', [UsersController::class, 'update'])->name('users.update');
        Route::delete('/{user}/delete', [UsersController::class, 'destroy'])->name('users.destroy');
        Route::resource('roles', RolesController::class);
        Route::resource('permissions', PermissionsController::class);
        Route::get('/profile', [UsersController::class, 'UserProfile'])->name('profile');
        Route::get('/profile/{id}/edit', [UsersController::class, 'UserProfileEditForm'])->name('profile.edit');
        Route::post('/profile/{id}/update', [UsersController::class, 'UserProfileEditFormSubmit'])->name('profile.update');
        Route::get('/change-password', [UsersController::class, 'changePasswordForm'])->name('password.change');
        Route::post('/update-password', [UsersController::class, 'updatePassword'])->name('password.update');
    });
    
    Route::get('/clear-cache', [CacheController::class, 'clearCache'])->name('clear-cache');
    Route::get('database-management', [DatabaseController::class, 'showTables'])->name('show.tables');
    Route::post('truncate-tables', [DatabaseController::class, 'truncateTables'])->name('truncate.tables');
    Route::get('backup-database', [DatabaseController::class, 'backupDatabase'])->name('backup.database');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/filtered-data', [DashboardController::class, 'getFilteredProductData'])
    ->name('dashboard.filtered-data');
    Route::get('get-visitor-stats', [DashboardController::class, 'getVisitorStats'])->name('get-visitor-stats');
    Route::get('get-visitor-list', [DashboardController::class, 'getVisitorList'])->name('get-visitor-list');
    Route::post('visitors/bulk-delete',[DashboardController::class,'bulkDeleteVisitor'])->name('visitors.bulk-delete');
    
    Route::get('brand', [BrandController::class, 'index'])->name('brand');
    Route::post('brand/create', [BrandController::class, 'create'])->name('brand.create');
    Route::post('/brand', [BrandController::class, 'store'])->name('brand.store');
    Route::post('/update-status/{brand}', [BrandController::class, 'updateStatus'])->name('updateStatus');
    Route::post('brand/edit', [BrandController::class, 'edit'])->name('brand.edit');
    Route::post('brand/update/{brand}', [BrandController::class, 'updateBrand'])->name('brand.update');
    Route::delete('brand/delete/{brand}', [BrandController::class, 'deleteBrand'])->name('brand.delete');
    /**label */
    Route::get('label', [LabelController::class, 'index'])->name('label');
    Route::post('label/create', [LabelController::class, 'create'])->name('label.create');
    Route::post('/label', [LabelController::class, 'store'])->name('label.store');
    Route::post('label/edit/{label}', [LabelController::class, 'edit'])->name('label.edit');
    Route::post('label/update/{label}', [LabelController::class, 'updateLabel'])->name('label.update');
    Route::delete('label/delete/{label}', [LabelController::class, 'deleteLabel'])->name('label.delete');
    Route::get('label-product/{labelId}', [LabelController::class, 'labelProduct'])->name('label-product');
    Route::post('label-product-form-submit/{labelId}', [LabelController::class, 'labelProductFormSubmit'])->name('label-product-form.submit');
    /**category route */
    Route::get('category', [CategoryController::class, 'index'])->name('category');
    Route::post('category/create', [CategoryController::class, 'create'])->name('category.create');
    Route::post('/category', [CategoryController::class, 'store'])->name('category.store');
    Route::post('category/edit/{category}', [CategoryController::class, 'edit'])->name('category.edit');
    Route::post('category/update/{category}', [CategoryController::class, 'updateCategory'])->name('category.update');
    Route::delete('category/delete/{category}', [CategoryController::class, 'deletaCategory'])->name('category.delete');
    Route::get('category/{id}', [CategoryController::class, 'show'])->name('category.show');
    Route::post('mapped-category-attributes-front/submit', [CategoryController::class, 'saveMappedCategoryAttributes'])->name('mappedCategoryAttributesFront.submit');
    /**Category sort */
    Route::post('category/sort', [CategoryController::class, 'updateSort'])->name('category.sort');
    /**subcategory */
    Route::get('subcategory', [SubcategoryController::class, 'index'])->name('subcategory');
    Route::post('subcategory/create', [SubcategoryController::class, 'create'])->name('subcategory.create');
    Route::post('/subcategory', [SubcategoryController::class, 'store'])->name('subcategory.store');
    Route::post('subcategory/edit/{subcategory}', [SubcategoryController::class, 'edit'])->name('subcategory.edit');
    Route::post('subcategory/update/{subcategory}', [SubcategoryController::class, 'updateSubcategory'])->name('subcategory.update');
    /**subcategory */
    /**Attributes */
    Route::get('attributes', [AttributeController::class, 'index'])->name('attributes');
    Route::post('attributes/create', [AttributeController::class, 'create'])->name('attributes.create');
    Route::post('/attributes', [AttributeController::class, 'store'])->name('attributes.store');
    Route::post('attributes/edit/{attributes}', [AttributeController::class, 'edit'])->name('attributes.edit');
    Route::post('attributes/update/{attributes}', [AttributeController::class, 'updateAttributes'])->name('attributes.update');

    Route::get('attributes-option/{attributes}', [AttributeController::class, 'attributesOption'])->name('attributes-option');
    Route::post('merge-attributes-value', [AttributeController::class, 'mergeAttributesValue'])->name('merge-attributes-value');
    Route::post('merge-attributes-value/submit', [AttributeController::class, 'mergeAttributesValueFormSubmit'])->name('merge-attributes-value.submit');

    Route::post('attributes-value-upload-img', [AttributeController::class, 'showForm'])->name('attributes-value-upload-img');
    Route::post('attributes-value-upload-img/submit', [AttributeController::class, 'showFormSubmit'])->name('attributes-value-upload-img.submit');
    Route::post('/attributes-value', [AttributeController::class, 'attributesValueStore'])->name('attributes-value.store');
    Route::get('attributesvalue-list', [AttributeController::class, 'attributesValueList'])->name('attributesvalue-list');
    /**attributes value wise update gst and hsn code */
    Route::get('update-hsn-gst-with-attributes-value/{attributes_id}/{category_id}', [AttributeController::class, 'updateHsnGstWithAttributesValue'])->name('update-hsn-gst-with-attributes-value');
    Route::post('/update-hsn-gst-attributes-value', [AttributeController::class, 'updateHsnGstAttributesValueFormSubmit'])->name('update-hsn-gst-attributes-value');
    Route::post('get-hsn-and-gst', [AttributeController::class, 'getHsnAndGst'])->name('get-hsn-and-gst');    
    /**attributes value wise update gst and hsn code */
    Route::post('/attributes-value/edit/{attributesValue}', [AttributeController::class, 'attributesValueEdit'])->name('attributes-value.edit');
    Route::post('attributes-value/update/{attributesValue}', [AttributeController::class, 'updateAttributesValue'])->name('attributes-value.update');
    Route::delete('attributes-value/delete/{attributesValue}', [AttributeController::class, 'deletaAttributesValue'])->name('attributes-value.delete');
    Route::post('/attribute-values/sort', [AttributeController::class, 'sort'])->name('attribute-values.sort');
    Route::get('product-catalog-attributes-value/{value}', [AttributeController::class, 'productCatalogWithAttributesValue'])->name('product-catalog-attributes-value');
    /**Attributes */
    /**Product route */
    Route::get('autocomplete/products', [ProductsController::class, 'autocompleteProductsAll'])->name('autocomplete.products');
    Route::resource('product', ProductsController::class);
    Route::post('products/{id}/status', [ProductsController::class, 'changeProductStatus'])->name('products.status');    
    Route::post('/products/bulk-delete', [ProductsController::class, 'bulkDelete'])->name('product.bulkDelete');
    Route::post('/products/modal-image-form', [ProductsController::class, 'imageUploadModalForm'])->name('products.modal-image-form');
    Route::post('/products/modal-image-form/submit', [ProductsController::class, 'imageUploadModalFormSubmit'])->name('products.modal-image-form.submit');    
    Route::post('/get-filtered-attributes', [ProductsController::class, 'getFilteredAttributes'])->name('getFilteredAttributes');
    Route::post('/add-more-attributes-row', [ProductsController::class, 'addMoreAttributesRow'])->name('addMoreAttributesRow');
    Route::get('product/image/delete/{id}', [ProductsController::class, 'deleteImage'])->name('product.image.delete');
    Route::get('/export-product', [ProductsController::class, 'exportProduct'])->name('export.product');
    Route::get('product/excel/import', [ProductsController::class, 'importExcelProduct'])->name('product.excel.import');
    Route::post('/product/excel/store', [ProductsController::class, 'ExcelStore'])->name('product.excel.store');
    Route::post('/product-image/sort', [ProductsController::class, 'sort'])->name('product-image.sort');
    Route::get('/product-update-gst', [ProductsController::class, 'updateProductListWithGST'])->name('product-update-gst');
    Route::get('/product-update-gst/filter', [ProductsController::class, 'filterProductListWithHsnGst'])->name('product-update-gst.filter');
    Route::get('product-multiple-update', [ProductsController::class, 'productMultipleUpdatePage'])->name('product-multiple-update');
    Route::post('product-update-all', [ProductsController::class, 'productMultipleUpdatePageSubmit'])->name('product-update-all');    
    Route::POST('/product-update-gst/store', [ProductsController::class, 'updateHSNCodeGstFormSubmit'])->name('product-update-gst.store');
    Route::get('/autocomplete/products-storage', [ProductsController::class, 'autocompleteProductsStorage'])->name('autocomplete.products-storage');
    Route::post('/product/{id}/make-duplicate', [ProductsController::class, 'makeDuplicateProduct'])->name('product.make.duplicate');
    Route::post('products/{product}/tags', [ProductsController::class, 'updateTags'])->name('product.tags.update');
    /**Product route */ 
    /**inventory route */
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/create', [InventoryController::class, 'create'])->name('create');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [InventoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [InventoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [InventoryController::class, 'destroy'])->name('delete');
        // Import / Export
        Route::get('/export', [InventoryController::class, 'exportInventory'])->name('export');
        Route::get('/import', [InventoryController::class, 'importInventory'])->name('import');
        Route::post('/import', [InventoryController::class, 'inventoryImportForm'])->name('import.store');
    });
    /**inventory route */
    /**Order Route */
    Route::get('order-list', [OrderControllerBackend::class, 'showAllOrderList'])->name('order-list');
    Route::get('order-details/{id}', [OrderControllerBackend::class, 'showOrderDetails'])->name('order-details');
    Route::get('edit-order/{id}', [OrderControllerBackend::class, 'editOrder'])->name('edit-order');
    Route::delete('order-list/destroy/{id}', [OrderControllerBackend::class, 'orderDelete'])->name('order-list.destroy');
    Route::post('update-order-status/{orderId}', [OrderControllerBackend::class, 'updateOrderStatus'])->name('update-order-status');
    Route::get('download-invoice/{orderId}', [OrderControllerBackend::class, 'downloadInvoice'])->name('download-invoice'); 
    /**Order Route */ 
    Route::get('manage-storage', [StorageController::class, 'index'])->name('manage-storage');
    Route::get('manage-storage/create', [StorageController::class, 'create'])->name('manage-storage.create');
    Route::post('manage-storage/comment/submit/{id}', [StorageController::class, 'storageCommentSubmit'])->name('manage-storage.comment.submit');
    Route::post('manage-storage/submit', [StorageController::class, 'store'])->name('manage-storage.submit');
    Route::delete('manage-storage/{id}',  [StorageController::class, 'destroy'])->name('manage-storage.delete');
    Route::post('mapped-image-to-product/submit', [StorageController::class, 'mappedImageToProductSubmit'])->name('mapped-image-to-product.submit');
    Route::Resource('manage-customer', CustomerControllerBackend::class);
    Route::get('/customer/import', [CustomerControllerBackend::class, 'importForm'])->name('customer.importForm');
    Route::post('/customer/import', [CustomerControllerBackend::class, 'importFormSubmit'])->name('customer.import');
    Route::get('customer-wishlist/{id}', [CustomerControllerBackend::class, 'showCustomerWishlist'])->name('customer-wishlist');    
    Route::get('customer-orders/{id}', [CustomerControllerBackend::class, 'showCustomerOrdersList'])->name('customer-orders');

    Route::Resource('manage-related-product', RelatedProductController::class);    
    Route::resource('manage-banner', BannerController::class);
    Route::get('/product-autocomplete', [ProductsController::class,'productAutocomplete'])->name('product.autocomplete');
    Route::resource('primary-category', PrimaryCategoryController::class);
    Route::put('primary-category/update-status/{id}', [PrimaryCategoryController::class, 'updateStatus'])->name('primary-category.update-status');
    Route::resource('manage-client', OurClientController::class);
    Route::resource('manage-testimonials', TestimonialsController::class);
    Route::resource('manage-blog', BlogController::class);
    Route::resource('manage-faq', FaqController::class);
    
    Route::Resource('magic-ai-image-generator', MagicAiImageGeneratorController::class);

    Route::middleware(['auth'])->group(function () {
        Route::post('/ckeditor/upload', [CkeditorController::class, 'upload'])->name('ckeditor.upload');
        Route::get('/ckeditor/images', [CkeditorController::class, 'imageList'])->name('ckeditor.images');
        Route::delete('/ckeditor/image', [CkeditorController::class, 'deleteImage'])->name('ckeditor.delete');
    });
    Route::resource('tags', TagController::class);
});
