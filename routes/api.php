<?php

use App\Helpers\FilePaths;
use App\Models\Page;
use App\Http\Controllers\API\v1\{GalleryController, LikeController, PushNotificationController, Rest};
use App\Http\Controllers\API\v1\Auth\{LoginController, RegisterController, VerifyAuthController};
use App\Http\Controllers\API\v1\Dashboard\{Admin, Payment, User};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v1', 'middleware' => ['block.ip']], function () {

    // Methods without AuthCheck
    Route::post('/auth/register',                       [RegisterController::class, 'register'])
        ->middleware('sessions');

    Route::post('/auth/login',                          [LoginController::class, 'login'])
        ->middleware('sessions');

    Route::post('/auth/check/phone',                    [LoginController::class, 'checkPhone'])
        ->middleware('sessions');

    Route::post('/auth/logout',                         [LoginController::class, 'logout'])
        ->middleware('sessions');

    Route::post('/auth/verify/phone',                   [VerifyAuthController::class, 'verifyPhone'])
        ->middleware('sessions');

    Route::post('/auth/resend-verify',                  [VerifyAuthController::class, 'resendVerify'])
        ->middleware('sessions');

    Route::get('/auth/verify/{hash}',                   [VerifyAuthController::class, 'verifyEmail'])
        ->middleware('sessions');

    Route::post('/auth/after-verify',                   [VerifyAuthController::class, 'afterVerifyEmail'])
        ->middleware('sessions');

    Route::post('/auth/forgot/password',                [LoginController::class, 'forgetPassword'])
        ->middleware('sessions');

    Route::post('/auth/forgot/password/before',        [LoginController::class, 'forgetPasswordBefore'])
        ->middleware('sessions');

    Route::post('/auth/forgot/password/confirm',        [LoginController::class, 'forgetPasswordVerify'])
        ->middleware('sessions');

    Route::post('/auth/forgot/email-password',          [LoginController::class, 'forgetPasswordEmail'])
        ->middleware('sessions');

    Route::post('/auth/forgot/email-password/{hash}',   [LoginController::class, 'forgetPasswordVerifyEmail'])
        ->middleware('sessions');

//    Route::get('/login/{provider}',                 [LoginController::class,'redirectToProvider']);
    Route::post('/auth/{provider}/callback',        [LoginController::class, 'handleProviderCallback']);

    Route::group(['prefix' => 'install'], function () {
        Route::get('/init/check',                   [Rest\InstallController::class, 'checkInitFile']);
        Route::post('/init/set',                    [Rest\InstallController::class, 'setInitFile']);
        Route::post('/database/update',             [Rest\InstallController::class, 'setDatabase']);
        Route::post('/admin/create',                [Rest\InstallController::class, 'createAdmin']);
        Route::post('/migration/run',               [Rest\InstallController::class, 'migrationRun']);
        Route::post('/check/licence',               [Rest\InstallController::class, 'licenceCredentials']);
    });

    Route::group(['prefix' => 'rest'], function () {

        /* Languages */
        Route::get('bos-ya/test',                   [Rest\TestController::class,    'bosYaTest']);
        Route::get('project/version',               [Rest\SettingController::class, 'projectVersion']);
        Route::get('timezone',                      [Rest\SettingController::class, 'timeZone']);
        Route::get('translations/paginate',         [Rest\SettingController::class, 'translationsPaginate']);
        Route::get('settings',                      [Rest\SettingController::class, 'settingsInfo']);
        Route::get('referral',                      [Rest\SettingController::class, 'referral']);
        Route::get('system/information',            [Rest\SettingController::class, 'systemInformation']);
        Route::get('stat',                          [Rest\SettingController::class, 'stat']);

        Route::get('added-review',                  [Rest\ReviewController::class, 'addedReview']);

        /* Languages */
        Route::get('languages/default',             [Rest\LanguageController::class, 'default']);
        Route::get('languages/active',              [Rest\LanguageController::class, 'active']);
        Route::get('languages/{id}',                [Rest\LanguageController::class, 'show']);
        Route::get('languages',                     [Rest\LanguageController::class, 'index']);

        /* Currencies */
        Route::get('currencies',                    [Rest\CurrencyController::class, 'index']);
        Route::get('currencies/active',             [Rest\CurrencyController::class, 'active']);

        /* Products */
        Route::get('products/reviews/{uuid}',       [Rest\ProductController::class, 'reviews']);
        Route::get('products',                      [Rest\ProductController::class, 'productsWithCategories']);
        Route::get('products/paginate',             [Rest\ProductController::class, 'paginate']);
        Route::get('products/ads',                  [Rest\ProductController::class, 'adsPaginate']);
        Route::get('products/brand/{id}',           [Rest\ProductController::class, 'productsByBrand']);
        Route::get('products/category/{uuid}',      [Rest\ProductController::class, 'productsByCategoryUuid']);
        Route::get('products/search',               [Rest\ProductController::class, 'productsSearch']);
        Route::get('products/ids',                  [Rest\ProductController::class, 'productsByIDs']);
        Route::get('compare',                       [Rest\ProductController::class, 'compare']);
        Route::get('products/{uuid}',               [Rest\ProductController::class, 'show']);
        Route::get('products/{slug}/phone',         [Rest\ProductController::class, 'showPhone']);
        Route::get('products/{slug}/message',       [Rest\ProductController::class, 'incrementMessageClickCount']);
        Route::get('products/related/{slug}',       [Rest\ProductController::class, 'related']);
        Route::get('products/{id}/reviews-group-rating', [Rest\ProductController::class, 'reviewsGroupByRating'])
            ->where('id', '[0-9]+');

        /* Categories */
        Route::get('categories/types',              [Rest\CategoryController::class, 'types']);
        Route::get('categories/parent',             [Rest\CategoryController::class, 'parentCategory']);
        Route::get('categories/children/{id}',      [Rest\CategoryController::class, 'childrenCategory']);
        Route::get('categories/paginate',           [Rest\CategoryController::class, 'paginate']);
        Route::get('categories/select-paginate',    [Rest\CategoryController::class, 'selectPaginate']);
        Route::get('categories/search',             [Rest\CategoryController::class, 'categoriesSearch']);
        Route::get('categories/{uuid}',             [Rest\CategoryController::class, 'show']);
        Route::get('categories/slug/{slug}',        [Rest\CategoryController::class, 'showSlug']);

        /* Brands */
        Route::get('brands/paginate',               [Rest\BrandController::class, 'paginate']);
        Route::get('brands/{id}',                   [Rest\BrandController::class, 'show']);
        Route::get('brands/slug/{slug}',            [Rest\BrandController::class, 'showSlug']);

        /* LandingPage */
        Route::get('landing-pages/paginate',        [Rest\LandingPageController::class, 'paginate']);
        Route::get('landing-pages/{type}',          [Rest\LandingPageController::class, 'show']);

        /* Banners */
        Route::get('banners/paginate',              [Rest\BannerController::class, 'paginate']);
        Route::get('banners/{id}',                  [Rest\BannerController::class, 'show']);

        /* FAQS */
        Route::get('faqs/paginate',                 [Rest\FAQController::class, 'paginate']);

        /* Payments */
        Route::get('payments',                      [Rest\PaymentController::class, 'index']);
        Route::get('payments/{id}',                 [Rest\PaymentController::class, 'show']);

        /* Blogs */
        Route::get('blogs/paginate',                [Rest\BlogController::class, 'paginate']);
        Route::get('blogs/{uuid}',                  [Rest\BlogController::class, 'show']);
        Route::get('blog-by-id/{id}',               [Rest\BlogController::class, 'showById']);
        Route::get('blogs/{id}/reviews',            [Rest\BlogController::class, 'reviews'])
            ->where('id', '[0-9]+');

        Route::get('blogs/{id}/reviews-group-rating', [Rest\BlogController::class, 'reviewsGroupByRating'])
            ->where('id', '[0-9]+');

        Route::get('term',                          [Rest\FAQController::class, 'term']);

        Route::get('policy',                        [Rest\FAQController::class, 'policy']);

        /* Stories */
        Route::get('stories/paginate',              [Rest\StoryController::class, 'paginate']);

        /* Tags */
        Route::get('tags/paginate',                 [Rest\TagController::class, 'paginate']);

        Route::get('product-histories/paginate',    [Rest\ProductController::class, 'history']);

        Route::get('careers/paginate',              [Rest\CareerController::class, 'index']);
        Route::get('careers/{id}',                  [Rest\CareerController::class, 'show']);

        Route::get('pages/paginate',                [Rest\PageController::class, 'index']);
        Route::get('pages/{type}',                  [Rest\PageController::class, 'show'])
            ->where('type', implode('|', Page::TYPES));

        Route::apiResource('regions',   Rest\RegionController::class)->only(['index', 'show']);
        Route::get('check/countries/{id}',        [Rest\CountryController::class, 'checkCountry']);
        Route::apiResource('countries', Rest\CountryController::class)->only(['index', 'show']);
        Route::apiResource('cities',    Rest\CityController::class)->only(['index', 'show']);
        Route::apiResource('areas',     Rest\AreaController::class)->only(['index', 'show']);
        Route::get('filter',                      [Rest\FilterController::class, 'filter']);
        Route::get('search',                      [Rest\FilterController::class, 'search']);

        Route::get('users/reviews',            [Rest\ReviewController::class, 'reviews'])
            ->where('id', '[0-9]+');
        Route::get('users/{id}/reviews-group-rating', [Rest\ReviewController::class, 'reviewsGroupByRating'])
            ->where('id', '[0-9]+');

        Route::get('attributes',      [Rest\AttributeController::class, 'paginate']);
        Route::get('attributes/{id}', [Rest\AttributeController::class, 'show']);

        $ads = FilePaths::routes()['ads']['rest'];

        if (file_exists($ads)) {
            require_once $ads;
        }

    });

    Route::group(['prefix' => 'payments', 'middleware' => ['sanctum.check'], 'as' => 'payment.'], function () {
        /* Transactions */
        Route::post('{type}/{id}/transactions', [Payment\TransactionController::class, 'store']);
        Route::put('{type}/{id}/transactions',  [Payment\TransactionController::class, 'updateStatus']);
    });

    Route::group(['prefix' => 'dashboard', 'middleware' => ['sanctum.check']], function () {

        /* Galleries */
        Route::get('/galleries/paginate',               [GalleryController::class, 'paginate']);
        Route::get('/galleries/types',                  [GalleryController::class, 'types']);
        Route::get('/galleries/storage/files',          [GalleryController::class, 'getStorageFiles']);
        Route::post('/galleries/storage/files/delete',  [GalleryController::class, 'deleteStorageFile']);
        Route::post('/galleries',                       [GalleryController::class, 'store']);
        Route::post('/galleries/store-many',            [GalleryController::class, 'storeMany']);

        // Likes
        Route::post('like/store-many',          [LikeController::class, 'storeMany']);
        Route::apiResource('likes',   LikeController::class);

        // Notifications
        Route::apiResource('notifications', PushNotificationController::class)->only(['index', 'show', 'destroy']);
        Route::post('notifications/{id}/read-at',   [PushNotificationController::class, 'readAt']);
        Route::post('notifications/read-all',       [PushNotificationController::class, 'readAll']);
        Route::delete('notifications/delete-all',   [PushNotificationController::class, 'deleteAll']);

        // USER BLOCK
        Route::group(['prefix' => 'user', 'middleware' => ['sanctum.check'], 'as' => 'user.'], function () {
            Route::get('chat/users/{id}',                       [User\ProfileController::class, 'chatShowById']);
            Route::get('chat-users',                            [User\ProfileController::class, 'chatUsersGet']);
            Route::get('admin-info',                            [User\ProfileController::class, 'adminInfo']);
            Route::get('profile/show',                          [User\ProfileController::class, 'show']);
            Route::put('profile/update',                        [User\ProfileController::class, 'update']);
            Route::put('profile/lang/update',                   [User\ProfileController::class, 'langUpdate']);
            Route::put('profile/currency/update',               [User\ProfileController::class, 'currencyUpdate']);
            Route::delete('profile/delete',                     [User\ProfileController::class, 'delete']);
            Route::post('profile/firebase/token/update',        [User\ProfileController::class, 'fireBaseTokenUpdate']);
            Route::post('profile/password/update',              [User\ProfileController::class, 'passwordUpdate']);
            Route::get('profile/notifications-statistic',       [User\ProfileController::class, 'notificationStatistic']);
            Route::get('search-sending',                        [User\ProfileController::class, 'searchSending']);

            Route::get('orders/paginate',                       [User\OrderController::class, 'paginate']);
            Route::post('orders/review/{id}',                   [User\OrderController::class, 'addOrderReview']);
            Route::get('orders/{id}/get-all',                   [User\OrderController::class, 'ordersByParentId']);
            Route::apiResource('orders',              User\OrderController::class)->except('index');

            Route::get('wallet/histories',                     [User\WalletController::class, 'walletHistories']);
            Route::post('wallet/withdraw',                     [User\WalletController::class, 'store']);
            Route::post('wallet/history/{uuid}/status/change', [User\WalletController::class, 'changeStatus']);
            Route::post('wallet/send',                         [User\WalletController::class, 'send']);

            /* Transaction */
            Route::get('transactions/paginate',                 [User\TransactionController::class, 'paginate']);
            Route::get('transactions/{id}',                     [User\TransactionController::class, 'show']);

            /* User Activity */
            Route::get('user-activities',                       [User\UserActivityController::class, 'index']);
            Route::post('user-activities',                      [User\UserActivityController::class, 'storeMany']);

            /* Ticket */
            Route::get('tickets/paginate',                      [User\TicketController::class, 'paginate']);
            Route::apiResource('tickets',             User\TicketController::class);

            /* Export */
            Route::get('export/order/{id}/pdf',                 [User\ExportController::class, 'orderExportPDF']);
            Route::get('export/all/order/{id}/pdf',             [User\ExportController::class, 'exportByParentPDF']);

            Route::post('update/notifications',  [User\ProfileController::class, 'notificationsUpdate']);
            Route::get('notifications',          [User\ProfileController::class, 'notifications']);

            Route::post('stripe-process',        [Payment\StripeController::class,       'processTransaction']);
            Route::post('razorpay-process',      [Payment\RazorPayController::class,     'processTransaction']);
            Route::post('paystack-process',      [Payment\PayStackController::class,     'processTransaction']);
            Route::post('paytabs-process',       [Payment\PayTabsController::class,      'processTransaction']);
            Route::post('flutter-wave-process',  [Payment\FlutterWaveController::class,  'processTransaction']);
            Route::post('mercado-pago-process',  [Payment\MercadoPagoController::class,  'processTransaction']);
            Route::post('paypal-process',        [Payment\PayPalController::class,       'processTransaction']);
            Route::post('moya-sar-process',      [Payment\MoyasarController::class,      'processTransaction']);
            Route::post('mollie-process',        [Payment\MollieController::class,       'processTransaction']);
            Route::post('zain-cash-process',     [Payment\ZainCashController::class,     'processTransaction']);
            Route::post('maksekeskus-process',   [Payment\MaksekeskusController::class,  'processTransaction']);
            Route::post('iyzico-process',        [Payment\IyzicoController::class,       'processTransaction']);
            Route::post('pay-fast-process',      [Payment\PayFastController::class, 	 'processTransaction']);

            /* Product review */
            Route::post('products/review/{uuid}', [User\ProductController::class, 'addProductReview']);

            /* Blogs review */
            Route::post('blogs/review/{id}',      [User\BlogController::class, 'addReviews']);

            /* Products */
            Route::get('products/paginate',              [User\ProductController::class, 'paginate']);
            Route::get('products/search',                [User\ProductController::class, 'productsSearch']);
            Route::post('products/{uuid}/active',        [User\ProductController::class, 'setActive']);
            Route::apiResource('products',     User\ProductController::class);
            Route::delete('products/delete',             [User\ProductController::class, 'destroy']);

            /* Units */
            Route::get('units/paginate', [User\UnitController::class, 'paginate']);
            Route::get('units/{id}',     [User\UnitController::class, 'show']);

            /* My resume */
            Route::get('my-resume',  [User\UserResumeController::class, 'show']);
            Route::post('my-resume', [User\UserResumeController::class, 'store']);

            /* Work request */
            Route::apiResource('work-requests',         User\WorkRequestController::class);
            Route::get('product-work-requests',           [User\WorkRequestController::class, 'productWorkRequestIndex']);
            Route::get('product/{product}/work-requests', [User\WorkRequestController::class, 'productWorkRequestShow']);
            Route::get('product-work-requests/{id}',      [User\WorkRequestController::class, 'workRequestShow']);

            Route::post('product/feedback',         [User\FeedbackController::class, 'store']);

            Route::get('statistics',                [User\DashboardController::class, 'statistics']);
            Route::get('statistics/products',       [User\DashboardController::class, 'productsStatistic']);

            $ads = FilePaths::routes()['ads']['user'];

            if (file_exists($ads)) {
                require_once $ads;
            }
        });

        // ADMIN BLOCK
        Route::group(['prefix' => 'admin', 'middleware' => ['sanctum.check', 'role:seller|manager'], 'as' => 'admin.'], function () {

            /* Dashboard */
            Route::get('timezones',                 [Admin\DashboardController::class, 'timeZones']);
            Route::get('timezone',                  [Admin\DashboardController::class, 'timeZone']);
            Route::post('timezone',                 [Admin\DashboardController::class, 'timeZoneChange']);

            Route::get('statistics',                [Admin\DashboardController::class, 'statistics']);
            Route::get('statistics/products',       [Admin\DashboardController::class, 'productsStatistic']);
            Route::get('statistics/users',          [Admin\DashboardController::class, 'usersStatistic']);

            /* Terms & Condition */
            Route::post('term',                     [Admin\TermsController::class, 'store']);
            Route::get('term',                      [Admin\TermsController::class, 'show']);
            Route::get('term/drop/all',             [Admin\TermsController::class, 'dropAll']);

            /* Privacy & Policy */
            Route::post('policy',                   [Admin\PrivacyPolicyController::class, 'store']);
            Route::get('policy',                    [Admin\PrivacyPolicyController::class, 'show']);
            Route::get('policy/drop/all',           [Admin\PrivacyPolicyController::class, 'dropAll']);

            /* Reviews */
            Route::get('reviews/paginate',          [Admin\ReviewController::class, 'paginate']);
            Route::apiResource('reviews', Admin\ReviewController::class);
            Route::delete('reviews/delete',         [Admin\ReviewController::class, 'destroy']);
            Route::get('reviews/drop/all',          [Admin\ReviewController::class, 'dropAll']);

            /* Languages */
            Route::get('languages/default',             [Admin\LanguageController::class, 'getDefaultLanguage']);
            Route::post('languages/default/{id}',       [Admin\LanguageController::class, 'setDefaultLanguage']);
            Route::get('languages/active',              [Admin\LanguageController::class, 'getActiveLanguages']);
            Route::post('languages/{id}/image/delete',  [Admin\LanguageController::class, 'imageDelete']);
            Route::apiResource('languages',   Admin\LanguageController::class);
            Route::delete('languages/delete',           [Admin\LanguageController::class, 'destroy']);
            Route::get('languages/drop/all',            [Admin\LanguageController::class, 'dropAll']);

            /* Currencies */
            Route::get('currencies/default',            [Admin\CurrencyController::class, 'getDefaultCurrency']);
            Route::post('currencies/default/{id}',      [Admin\CurrencyController::class, 'setDefaultCurrency']);
            Route::get('currencies/active',             [Admin\CurrencyController::class, 'getActiveCurrencies']);
            Route::apiResource('currencies',  Admin\CurrencyController::class);
            Route::delete('currencies/delete',          [Admin\CurrencyController::class, 'destroy']);
            Route::get('currencies/drop/all',           [Admin\CurrencyController::class, 'dropAll']);

            /* Categories */
            Route::get('categories/export',                 [Admin\CategoryController::class, 'fileExport']);
            Route::post('categories/{uuid}/image/delete',   [Admin\CategoryController::class, 'imageDelete']);
            Route::get('categories/search',                 [Admin\CategoryController::class, 'categoriesSearch']);
            Route::get('categories/paginate',               [Admin\CategoryController::class, 'paginate']);
            Route::get('categories/select-paginate',        [Admin\CategoryController::class, 'selectPaginate']);
            Route::post('categories/import',                [Admin\CategoryController::class, 'fileImport']);
            Route::apiResource('categories',      Admin\CategoryController::class);
            Route::post('category-input/{uuid}',            [Admin\CategoryController::class, 'changeInput']);
            Route::post('categories/{uuid}/active',         [Admin\CategoryController::class, 'changeActive']);
            Route::post('categories/{uuid}/status',         [Admin\CategoryController::class, 'changeStatus']);
            Route::delete('categories/delete',              [Admin\CategoryController::class, 'destroy']);
            Route::get('categories/drop/all',               [Admin\CategoryController::class, 'dropAll']);

            /* Brands */
            Route::get('brands/export',             [Admin\BrandController::class, 'fileExport']);
            Route::post('brands/import',            [Admin\BrandController::class, 'fileImport']);
            Route::get('brands/paginate',           [Admin\BrandController::class, 'paginate']);
            Route::get('brands/search',             [Admin\BrandController::class, 'brandsSearch']);
            Route::apiResource('brands',  Admin\BrandController::class);
            Route::delete('brands/delete',          [Admin\BrandController::class, 'destroy']);
            Route::get('brands/drop/all',           [Admin\BrandController::class, 'dropAll']);

            /* Banner */
            Route::get('banners/paginate',          [Admin\BannerController::class, 'paginate']);
            Route::post('banners/active/{id}',      [Admin\BannerController::class, 'setActiveBanner']);
            Route::apiResource('banners', Admin\BannerController::class);
            Route::delete('banners/delete',         [Admin\BannerController::class, 'destroy']);
            Route::get('banners/drop/all',          [Admin\BannerController::class, 'dropAll']);

            /* LandingPage */
            Route::apiResource('landing-pages',  Admin\LandingPageController::class);
            Route::delete('landing-pages/delete',   [Admin\LandingPageController::class, 'destroy']);
            Route::get('landing-pages/drop/all',    [Admin\LandingPageController::class, 'dropAll']);

            /* Units */
            Route::get('units/paginate',            [Admin\UnitController::class, 'paginate']);
            Route::post('units/active/{id}',        [Admin\UnitController::class, 'setActiveUnit']);
            Route::delete('units/delete',           [Admin\UnitController::class, 'destroy']);
            Route::get('units/drop/all',            [Admin\UnitController::class, 'dropAll']);
            Route::apiResource('units',  Admin\UnitController::class)->except('destroy');

            /* Products */
            Route::get('products/export',                [Admin\ProductController::class, 'fileExport']);
            Route::get('most-popular/products',          [Admin\ProductController::class, 'mostPopulars']);
            Route::post('products/import',               [Admin\ProductController::class, 'fileImport']);
            Route::get('products/paginate',              [Admin\ProductController::class, 'paginate']);
            Route::get('products/search',                [Admin\ProductController::class, 'productsSearch']);
            Route::post('products/{uuid}/active',        [Admin\ProductController::class, 'setActive']);
            Route::post('products/{uuid}/status/change', [Admin\ProductController::class, 'setStatus']);
            Route::apiResource('products',     Admin\ProductController::class);
            Route::delete('products/delete',             [Admin\ProductController::class, 'destroy']);
            Route::get('products/drop/all',              [Admin\ProductController::class, 'dropAll']);

            /* Orders */
            Route::get('order/export',                   [Admin\OrderController::class, 'fileExport']);
            Route::post('order/import',                  [Admin\OrderController::class, 'fileImport']);
            Route::get('orders/paginate',                [Admin\OrderController::class, 'paginate']);
            Route::apiResource('orders',       Admin\OrderController::class);
            Route::delete('orders/delete',               [Admin\OrderController::class, 'destroy']);
            Route::get('orders/drop/all',                [Admin\OrderController::class, 'dropAll']);
            Route::get('user-orders/{id}',               [Admin\OrderController::class, 'userOrder']);
            Route::get('user-orders/{id}/paginate',      [Admin\OrderController::class, 'userOrders']);

            /* Users */
            Route::get('users/search',                  [Admin\UserController::class, 'usersSearch']);
            Route::get('users/paginate',                [Admin\UserController::class, 'paginate']);
            Route::get('users/drop/all',                [Admin\UserController::class, 'dropAll']);
            Route::post('users/{uuid}/role/update',     [Admin\UserController::class, 'updateRole']);
            Route::get('users/{uuid}/wallets/history',  [Admin\UserController::class, 'walletHistories']);
            Route::post('users/{uuid}/wallets',         [Admin\UserController::class, 'topUpWallet']);
            Route::post('users/{uuid}/active',          [Admin\UserController::class, 'setActive']);
            Route::post('users/{uuid}/password',        [Admin\UserController::class, 'passwordUpdate']);
            Route::get('users/{uuid}/login-as',         [Admin\UserController::class, 'loginAsUser']);
            Route::apiResource('users',       Admin\UserController::class);
            Route::delete('users/delete',               [Admin\UserController::class, 'destroy']);

            Route::get('roles', Admin\RoleController::class);

            /* Users Wallet Histories */
            Route::get('wallet/histories/paginate',     [Admin\WalletHistoryController::class, 'paginate']);
            Route::get('wallet/histories/drop/all',     [Admin\WalletHistoryController::class, 'dropAll']);
            Route::post('wallet/history/{uuid}/status/change', [Admin\WalletHistoryController::class, 'changeStatus']);
            Route::get('wallet/drop/all',               [Admin\WalletController::class, 'dropAll']);

            /* Payments */
            Route::post('payments/{id}/active/status', [Admin\PaymentController::class, 'setActive']);
            Route::apiResource('payments',   Admin\PaymentController::class)
                ->except('store', 'delete');

            Route::get('payments/drop/all',           [Admin\PaymentController::class, 'dropAll']);

            /* Translations */
            Route::get('translations/paginate',         [Admin\TranslationController::class, 'paginate']);
            Route::post('translations/import',          [Admin\TranslationController::class, 'import']);
            Route::get('translations/export',           [Admin\TranslationController::class, 'export']);
            Route::apiResource('translations',Admin\TranslationController::class);
            Route::delete('translations/delete',        [Admin\TranslationController::class, 'destroy']);
            Route::get('translations/drop/all',         [Admin\TranslationController::class, 'dropAll']);

            /* Transaction */
            Route::get('transactions/paginate',     [Admin\TransactionController::class, 'paginate']);
            Route::get('transactions/{id}',         [Admin\TransactionController::class, 'show']);
            Route::post('transactions/{id}',        [Admin\TransactionController::class, 'update']);
            Route::get('transactions/drop/all',     [Admin\TransactionController::class, 'dropAll']);

            /* Tickets */
            Route::get('tickets/paginate',          [Admin\TicketController::class, 'paginate']);
            Route::post('tickets/{id}/status',      [Admin\TicketController::class, 'setStatus']);
            Route::get('tickets/statuses',          [Admin\TicketController::class, 'getStatuses']);
            Route::apiResource('tickets', Admin\TicketController::class);
            Route::get('tickets/drop/all',          [Admin\TicketController::class, 'dropAll']);

            /* FAQS */
            Route::get('faqs/paginate',                 [Admin\FAQController::class, 'paginate']);
            Route::post('faqs/{uuid}/active/status',    [Admin\FAQController::class, 'setActiveStatus']);
            Route::apiResource('faqs',        Admin\FAQController::class)->except('index');
            Route::delete('faqs/delete',                [Admin\FAQController::class, 'destroy']);
            Route::get('faqs/drop/all',                 [Admin\FAQController::class, 'dropAll']);

            /* Blogs */
            Route::get('blogs/paginate',                [Admin\BlogController::class, 'paginate']);
            Route::post('blogs/{uuid}/publish',         [Admin\BlogController::class, 'blogPublish']);
            Route::post('blogs/{uuid}/active/status',   [Admin\BlogController::class, 'setActiveStatus']);
            Route::apiResource('blogs',       Admin\BlogController::class)->except('index');
            Route::delete('blogs/delete',               [Admin\BlogController::class, 'destroy']);
            Route::get('blogs/drop/all',                [Admin\BlogController::class, 'dropAll']);

            /* Settings */
            Route::get('settings/system/information',   [Admin\SettingController::class, 'systemInformation']);
            Route::get('settings/system/cache/clear',   [Admin\SettingController::class, 'clearCache']);
            Route::apiResource('settings',    Admin\SettingController::class);
            Route::get('settings/drop/all',             [Admin\SettingController::class, 'dropAll']);

            Route::post('backup/history',               [Admin\BackupController::class, 'download']);
            Route::get('backup/history',                [Admin\BackupController::class, 'histories']);
            Route::get('backup/drop/all',               [Admin\BackupController::class, 'dropAll']);

            // Auto updates
            Route::post('/project-upload', [Admin\ProjectController::class, 'projectUpload']);
            Route::post('/project-update', [Admin\ProjectController::class, 'projectUpdate']);

            /* Stories */
            Route::post('stories/upload',           [Admin\StoryController::class, 'uploadFiles']);
            Route::apiResource('stories', Admin\StoryController::class);
            Route::get('stories/{id}/active',       [Admin\StoryController::class, 'changeActive']);
            Route::delete('stories/delete',         [Admin\StoryController::class, 'destroy']);
            Route::get('stories/drop/all',          [Admin\StoryController::class, 'dropAll']);

            /* Tags */
            Route::apiResource('tags', Admin\TagController::class);
            Route::delete('tags/delete',         [Admin\TagController::class, 'destroy']);
            Route::get('tags/drop/all',          [Admin\TagController::class, 'dropAll']);

            /* Email Setting */
            Route::apiResource('email-settings',  Admin\EmailSettingController::class);
            Route::delete('email-settings/delete',          [Admin\EmailSettingController::class, 'destroy']);
            Route::get('email-settings/set-active/{id}',    [Admin\EmailSettingController::class, 'setActive']);
            Route::get('email-settings/drop/all',           [Admin\EmailSettingController::class, 'dropAll']);

            /* Email Subscriptions */
            Route::get('email-subscriptions',          [Admin\EmailSubscriptionController::class, 'emailSubscriptions']);
            Route::post('email-subscriptions/{id}',    [Admin\EmailSubscriptionController::class, 'setActive']);
            Route::get('email-subscriptions/drop/all', [Admin\EmailSubscriptionController::class, 'dropAll']);

            /* Email Templates */
            Route::get('email-templates/types',             [Admin\EmailTemplateController::class, 'types']);
            Route::apiResource('email-templates', Admin\EmailTemplateController::class);
            Route::delete('email-templates/delete',         [Admin\EmailTemplateController::class, 'destroy']);
            Route::get('email-templates/drop/all',          [Admin\EmailTemplateController::class, 'dropAll']);

            /* Notifications */
            Route::apiResource('notifications', Admin\NotificationController::class);
            Route::delete('notifications/delete',   [Admin\NotificationController::class, 'destroy']);
            Route::get('notifications/drop/all',    [Admin\NotificationController::class, 'dropAll']);

            /* PaymentPayload tags */
            Route::apiResource('payment-payloads',Admin\PaymentPayloadController::class);
            Route::delete('payment-payloads/delete',        [Admin\PaymentPayloadController::class, 'destroy']);
            Route::get('payment-payloads/drop/all',         [Admin\PaymentPayloadController::class, 'dropAll']);

            /* SmsPayload tags */
            Route::apiResource('sms-payloads',Admin\SmsPayloadController::class);
            Route::delete('sms-payloads/delete',        [Admin\SmsPayloadController::class, 'destroy']);
            Route::get('sms-payloads/drop/all',         [Admin\SmsPayloadController::class, 'dropAll']);

            Route::apiResource('referrals',       Admin\ReferralController::class);
            Route::get('referrals/transactions/paginate',   [Admin\ReferralController::class, 'transactions']);

            /* Report Categories */
            Route::get('categories/report/chart',   [Admin\CategoryController::class, 'reportChart']);

            /* Report Products */
            Route::get('products/report/chart',     [Admin\OrderReportController::class, 'reportChart']);
            Route::get('products/report/paginate',  [Admin\ProductController::class, 'reportPaginate']);

            /* Report Orders */
            Route::get('orders/report/chart',    [Admin\OrderReportController::class, 'reportChart']);
            Route::get('orders/report/paginate', [Admin\OrderReportController::class, 'reportChartPaginate']);

            /* Report Revenues */
            Route::get('revenue/report', [Admin\OrderReportController::class, 'revenueReport']);

            /* Report Overviews */
            Route::get('overview/carts',      [Admin\OrderReportController::class, 'overviewCarts']);
            Route::get('overview/products',   [Admin\OrderReportController::class, 'overviewProducts']);
            Route::get('overview/categories', [Admin\OrderReportController::class, 'overviewCategories']);

            /* Career */
            Route::apiResource('careers',Admin\CareerController::class);
            Route::delete('careers/delete',        [Admin\CareerController::class, 'destroy']);
            Route::get('careers/drop/all',         [Admin\CareerController::class, 'dropAll']);

            /* Pages */
            Route::apiResource('pages',Admin\PageController::class);
            Route::delete('pages/delete',        [Admin\PageController::class, 'destroy']);
            Route::get('pages/drop/all',         [Admin\PageController::class, 'dropAll']);

            /* Model logs */
            Route::get('model/logs/{id}',        [Admin\ModelLogController::class, 'show']);
            Route::get('model/logs/paginate',    [Admin\ModelLogController::class, 'paginate']);

            /* Regions */
            Route::apiResource('regions',      Admin\RegionController::class);
            Route::get('region/{id}/active',     [Admin\RegionController::class, 'changeActive']);
            Route::delete('regions/delete',      [Admin\RegionController::class, 'destroy']);
            Route::get('regions/drop/all',       [Admin\RegionController::class, 'dropAll']);

            /* Countries */
            Route::apiResource('countries',    Admin\CountryController::class);
            Route::get('country/{id}/active',    [Admin\CountryController::class, 'changeActive']);
            Route::delete('countries/delete',    [Admin\CountryController::class, 'destroy']);
            Route::get('countries/drop/all',     [Admin\CountryController::class, 'dropAll']);

            /* Cities */
            Route::apiResource('cities',       Admin\CityController::class);
            Route::get('city/{id}/active',       [Admin\CityController::class, 'changeActive']);
            Route::delete('cities/delete',       [Admin\CityController::class, 'destroy']);
            Route::get('cities/drop/all',        [Admin\CityController::class, 'dropAll']);

            /* Areas */
            Route::apiResource('areas',        Admin\AreaController::class);
            Route::get('area/{id}/active',       [Admin\AreaController::class, 'changeActive']);
            Route::delete('areas/delete',        [Admin\AreaController::class, 'destroy']);
            Route::get('areas/drop/all',         [Admin\AreaController::class, 'dropAll']);

            /* Ads Package */
            Route::apiResource('attributes', Admin\AttributeController::class);
            Route::post('attributes/create/many', [Admin\AttributeController::class, 'storeMany']);
            Route::delete('attributes/delete', [Admin\AttributeController::class, 'destroy']);
            Route::get('attributes/drop/all',  [Admin\AttributeController::class, 'dropAll']);

            $ads = FilePaths::routes()['ads']['admin'];

            if (file_exists($ads)) {
                require_once $ads;
            }

        });

    });

    Route::group(['prefix' => 'webhook'], function () {
        Route::any('stripe/payment',       [Payment\StripeController::class,      'paymentWebHook']);
        Route::any('razorpay/payment',     [Payment\RazorPayController::class,    'paymentWebHook']);
        Route::any('iyzico/payment',       [Payment\IyzicoController::class, 	  'processTransaction']);
        Route::any('paystack/payment',     [Payment\PayStackController::class,    'paymentWebHook']);
        Route::any('paytabs/payment',      [Payment\PayTabsController::class,     'paymentWebHook']);
        Route::any('flutter-wave/payment', [Payment\FlutterWaveController::class, 'paymentWebHook']);
        Route::any('paypal/payment',       [Payment\PayPalController::class,      'paymentWebHook']);
        Route::any('mercado-pago/payment', [Payment\MercadoPagoController::class, 'paymentWebHook']);
        Route::any('moya-sar/payment',     [Payment\MoyasarController::class,     'paymentWebHook']);
        Route::any('mollie/payment',       [Payment\MollieController::class,      'paymentWebHook']);
        Route::any('maksekeskus/payment',  [Payment\MaksekeskusController::class, 'paymentWebHook']);
    });
});
