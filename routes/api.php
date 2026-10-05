<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PointController;
use App\Http\Controllers\Admin\RedeemPointController;
use App\Http\Controllers\Admin\MemberLevelController;
use App\Http\Controllers\Admin\GiftController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\LogoController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ContactUsController;
use App\Http\Controllers\Admin\ListContactController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Customer\AuthCustomerController;
use App\Http\Controllers\Customer\CustomerDataController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Customer\CustomerTransactionController;
use App\Http\Controllers\Customer\ContactUsCustomerController;
use App\Http\Controllers\Customer\CustomerMemberLevelController;
use App\Http\Controllers\Customer\GiftCustomerController;
use App\Http\Controllers\Customer\EmailSubscriberController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\PartController;
use App\Http\Controllers\Customer\PromotionCustomersController;
use App\Http\Middleware\CanAccessMenu;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/check-route', fn() => 'ok');

Route::prefix('admins')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware([\App\Http\Middleware\ProgressiveRateLimit::class . ':5,1', \App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class])->name('logincheck');
    Route::get('/check-route', fn() => 'ok')->name('login');
    Route::post('/forgot_password', [AuthController::class, 'sendResetLinkEmail'])->middleware([\App\Http\Middleware\ProgressiveRateLimit::class . ':3,1', \App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class]);
    Route::post('/reset_password', [AuthController::class, 'reset'])->middleware([\App\Http\Middleware\ProgressiveRateLimit::class . ':3,1', \App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class]);

    Route::get('active-logo', [LogoController::class, 'activeLogo'])->name('logo.active');


    Route::middleware(['jwt.auth', \App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);

        Route::middleware([CanAccessMenu::class . ':Admins'])->group(function () {
            Route::apiResource('admin', AdminController::class);
            Route::get('adminEdit/{id}', [AdminController::class, 'edit']);
        });

        Route::middleware([CanAccessMenu::class . ':Roles'])->group(function () {
            Route::apiResource('role', RolesController::class);
            route::get('get-role', [AdminController::class, 'getRole'])->name('getRole');
            route::get('get-menu', [RolesController::class, 'getMenu'])->name('getMenu');
            route::get('get-edit-role/{id}', [RolesController::class, 'edit'])->name('editRole');
        });

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::middleware([CanAccessMenu::class . ':Promotions'])->group(function () {
            Route::apiResource('promotion', PromotionController::class);
            Route::get('edit-promotion/{id}', [PromotionController::class, 'edit'])->name('editPromotion');
        });

        Route::middleware([CanAccessMenu::class . ':Banners'])->group(function () {
            Route::apiResource('banner', BannerController::class);
            route::get('banner-edit/{id}', [BannerController::class, 'edit'])->name('editBanner');
        });

        Route::middleware([CanAccessMenu::class . ':ContactUs'])->group(function () {
            Route::apiResource('contact-us', ContactUsController::class);
            Route::apiResource('list-contact', ListContactController::class);
        });

        Route::middleware([CanAccessMenu::class . ':customer'])->group(function () {
            Route::apiResource('customer', CustomerController::class);
            Route::get('get-orders', [OrderController::class, 'getOrders']);
            Route::get('get-orders/{id}', [OrderController::class, 'getOrderDetail']);
            Route::get('get-customers', [OrderController::class, 'getCustomers']);
        });

        Route::middleware([CanAccessMenu::class . ':Orders'])->group(function () {
            Route::apiResource('order', OrderController::class);
            Route::get('get-categories', [OrderController::class, 'getCategories']);
            Route::get('get-brands', [OrderController::class, 'getBrands']);
        });

        Route::middleware([CanAccessMenu::class . ':Transactions'])->group(function () {
            Route::apiResource('transaction', TransactionController::class);
        });

        Route::middleware([CanAccessMenu::class . ':Brands'])->group(function () {
            Route::apiResource('brand', BrandController::class);
            route::get('brand-edit/{id}', [BrandController::class, 'edit'])->name('editBrand');
        });

        Route::get('dashboard', [DashboardController::class, 'data'])->name('data');

        Route::middleware([CanAccessMenu::class . ':Categories'])->group(function () {
            Route::apiResource('category', CategoryController::class);
        });

        Route::middleware([CanAccessMenu::class . ':Points'])->group(function () {
            Route::apiResource('point', PointController::class);
            route::get('point-edit/{id}', [PointController::class, 'edit'])->name('editPoint');
        });

        Route::middleware([CanAccessMenu::class . ':Gifts'])->group(function () {
            Route::apiResource('gift', GiftController::class);
            route::get('gift-edit/{id}', [GiftController::class, 'edit'])->name('editGift');
        });

        Route::prefix('gift-claims')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\GiftClaimController::class, 'index'])->name('admin.gift-claims.index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\GiftClaimController::class, 'show'])->name('admin.gift-claims.show');
            Route::post('/{id}/approve', [\App\Http\Controllers\Admin\GiftClaimController::class, 'approve'])->name('admin.gift-claims.approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\Admin\GiftClaimController::class, 'reject'])->name('admin.gift-claims.reject');
            Route::post('/{id}/complete', [\App\Http\Controllers\Admin\GiftClaimController::class, 'complete'])->name('admin.gift-claims.complete');
        });

        Route::apiResource('member_level', MemberLevelController::class);
        route::get('member_level-edit/{id}', [MemberLevelController::class, 'edit'])->name('editMemberLevel');

        Route::prefix('redeem-point')->group(function () {
            Route::get('/', [RedeemPointController::class, 'index'])->name('redeem-point.index');
            Route::post('/calculate/{customerId}', [RedeemPointController::class, 'calculate'])->name('redeem-point.calculate');
            Route::post('/claim', [RedeemPointController::class, 'claim'])->name('redeem-point.claim');
            Route::get('/history/{customerId}', [RedeemPointController::class, 'history'])->name('redeem-point.history');
            Route::get('/{customerId}', [RedeemPointController::class, 'show'])->name('redeem-point.show');
        });

       Route::apiResource('logo', LogoController::class);
       Route::get('logo-edit/{id}', [LogoController::class, 'edit'])->name('editLogo');
       Route::patch('logo/{id}/set-active', [LogoController::class, 'setActive'])->name('logo.setActive');

        Route::middleware([CanAccessMenu::class . ':Users'])->get('/users', function () {
            return response()->json(['message' => 'Users Page']);
        });

        Route::middleware([CanAccessMenu::class . ':Settings'])->get('/settings', function () {
            return response()->json(['message' => 'Settings Page']);
        });
    });
});

Route::get('part-color', [PartController::class, 'getColor'])->name(name: 'color');
Route::get('part-category', [PartController::class, 'getCategory'])->name(name: 'category');
Route::get('part-type-product', [PartController::class, 'getType'])->name(name: 'product-type');
Route::get('part-brand', [PartController::class, 'getBrand'])->name(name: 'brand');
Route::get('home-banner', [HomeController::class, 'getHomeBanner'])->name(name: 'brand');
Route::get('customer-promotion', [PromotionCustomersController::class, 'index'])->name(name: 'customer-promotion');


Route::post('email-subscriber', [EmailSubscriberController::class,'store'])->middleware(\App\Http\Middleware\CheckCsrfOrigin::class);
Route::prefix('customers')->group(function () {
    Route::post('/register', [AuthCustomerController::class, 'register'])->middleware(['throttle:10,1', \App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class])->name('register-customer');
    Route::post('/social-register-auth', [AuthCustomerController::class, 'socialRegister'])->middleware(['throttle:10,1', \App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class])->name('social-register-auth');
    Route::post('/social-login', [AuthCustomerController::class, 'socialLogin'])->middleware(['throttle:10,1', \App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class])->name('social-login-customer');
    Route::post('/login-customer', [AuthCustomerController::class, 'login'])->middleware(['throttle:8,1', \App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class])->name('login-customer');
    Route::post('/set-password', [AuthCustomerController::class, 'setPassword'])->middleware(['throttle:8,1', \App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class])->name('set-password');
    Route::post('/resend-verification', [AuthCustomerController::class, 'resendVerification'])->middleware(['throttle:5,1', \App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class])->name('resend-verification');
    Route::post('/verify-email', [AuthCustomerController::class, 'verifyEmail'])->middleware([\App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class])->name('verify-email');
    Route::get('/me', [AuthCustomerController::class, 'me']);
    Route::post('/logout', [AuthCustomerController::class, 'logout'])->middleware(\App\Http\Middleware\CheckCsrfOrigin::class);
    Route::post('/change-password', [AuthCustomerController::class, 'changePassword'])->middleware(\App\Http\Middleware\CheckCsrfOrigin::class);

   // Route::apiResource('contact-us', ContactUsCustomerController::class);
    Route::post('contact-us', [ContactUsCustomerController::class, 'store'])->middleware([\App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class]);
    Route::post('email-subscriber', [EmailSubscriberController::class, 'store'])->middleware([\App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class]);
    Route::middleware(['jwt.auth'])->group(function () {
        Route::apiResource('customer-data', CustomerDataController::class);
    });
    // Endpoint order customer (letakkan SEBELUM apiResource agar tidak konflik dengan /orders/{id})
    Route::middleware(['jwt.customer'])->group(function () {
        Route::get('get-orders', [CustomerOrderController::class, 'getOrders']);
        Route::get('get-orders/{id}', [CustomerOrderController::class, 'getOrderDetail']);
        Route::get('member-level', [CustomerMemberLevelController::class, 'show']);
        Route::get('gifts', [GiftCustomerController::class, 'index']);
        Route::post('gifts/claim', [GiftCustomerController::class, 'claim']);
        Route::get('gifts/claims', [GiftCustomerController::class, 'claims']);
        Route::apiResource('orders', CustomerOrderController::class);
    });
    Route::apiResource('transactions', CustomerTransactionController::class);
    Route::get('get-province', [CustomerDataController::class, 'getProvince']);
    Route::post('get-city', [CustomerDataController::class, 'getCity'])->middleware([\App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class]);
    Route::post('get-districts', [CustomerDataController::class, 'getDistricts'])->middleware([\App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class]);
    Route::post('get-subdistrict', [CustomerDataController::class, 'getSubDistrict'])->middleware([\App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class]);
    Route::post('get-postal-code', [CustomerDataController::class, 'getPostalCode'])->middleware([\App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class]);
    Route::post('create-address', [CustomerDataController::class, 'createAddress'])->middleware([\App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class]);
    Route::get('get-addresses', [CustomerDataController::class, 'getAddresses']);
    Route::post('delete-address', [CustomerDataController::class, 'deleteAddress'])->middleware([\App\Http\Middleware\CheckCsrfOrigin::class, \App\Http\Middleware\SanitizeXssInput::class]);
});
