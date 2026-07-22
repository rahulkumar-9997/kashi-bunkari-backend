<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\StateController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('states', [StateController::class, 'list']);
Route::get('menu', [MenuController::class, 'menu']);
Route::get('/home/banner', [HomeController::class, 'banner']);
Route::get('/home/category', [HomeController::class, 'homeCategoryProducts']);
Route::get('/home/collections', [HomeController::class, 'homeCollectionProducts']);
Route::get('/home/new-arrivals', [HomeController::class, 'newArrivals']);
Route::get('/home/popular-products', [HomeController::class, 'popularProducts']);
Route::get('/home/occasion', [HomeController::class, 'occasionProducts']);
Route::get('/home/client', [HomeController::class, 'client']);
Route::get('testimonials', [HomeController::class, 'testimonials']);
Route::get('faq', [HomeController::class, 'faq']);

Route::get('/home/blog', [BlogController::class, 'homeBlogList']);
Route::get('blog', [BlogController::class, 'blogList']);
Route::get('blog/{slug}', [BlogController::class, 'blogDetails']);
Route::get('search-suggestion', [SearchController::class, 'searchSuggestions']);
Route::get('search', [SearchController::class, 'searchProductList']);

Route::get('shop/{first}/{second?}/{third?}', [ProductController::class, 'productCatalogForAllParams']);
Route::get('products/{product_slug}/{attributes_value}', [ProductController::class, 'productDetails']);


Route::get('product-catalog/{category}/{attribute}/{value}', [ProductController::class, 'productCatalog']);
Route::get('category/{category}', [ProductController::class, 'productCategoryCatalog']);
Route::get('products/{product_slug}/{attributes_value}', [ProductController::class, 'productDetails']);

Route::post('/contact-submit', [ContactController::class, 'submit']);


Route::prefix('customer')->group(function () {
    /* Public APIs */
    Route::controller(CustomerAuthController::class)->group(function () {        
        Route::post('/login', 'loginOrCreateAccountWithOtp');
        Route::post('/send-otp', 'sendOtp');
        Route::post('/verify-otp', 'verifyOtpAndLogin');
        Route::post('/resend-otp', 'resendOtp')->middleware('throttle:5,1');
        Route::post('/check-contact', 'checkContactExists');
        Route::post('/google-login', 'googleLogin');
    });
    /* Protected APIs */
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::controller(CustomerController::class)->group(function () {
            Route::get('/profile', 'profile');
            Route::post('/update-profile', 'updateProfile');
            Route::post('/logout', 'logout');
        });
        Route::prefix('addresses')->controller(AddressController::class)->group(function () {
            Route::get('/', 'list');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::patch('/{id}/set-default', 'setDefault');
        });               
    });   
    
    
});
Route::middleware(['auth:sanctum'])->group(function () {
	Route::controller(WishlistController::class)->group(function () {
		Route::post('/wishlist/add', 'add');
		Route::get('/wishlist/list', 'list');
		Route::delete('/wishlist/{id}', 'remove');
	}); 
});

Route::prefix('cart')->middleware(['cart.optional-auth'])->group(function () {
    Route::post('/add', [CartController::class, 'addToCart']); 
    Route::get('/list', [CartController::class, 'cartList']); 
    Route::put('/{id}', [CartController::class, 'updateCartItem']); 
    Route::delete('/{id}', [CartController::class, 'removeFromCart']);
    Route::delete('/', [CartController::class, 'clearCart']);
});

Route::middleware(['cart.optional-auth'])->prefix('payment')->group(function () {
    Route::post('/create-order', [PaymentController::class, 'createOrder']);
    Route::post('/verify', [PaymentController::class, 'verifyPayment']);
});

Route::prefix('payment')->group(function () {
    Route::get('/promotions', [PaymentController::class, 'getPromotions']);
    Route::post('/shipping-info', [PaymentController::class, 'getShippingInfo']);
});
