<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    // Authentication routes
    $router->post('register', 'UserController@register');
    $router->post('login', 'UserController@login');

    // User management routes
    $router->get('users', 'UserController@index');
    $router->get('users/{id}', 'UserController@show');
    $router->put('users/{id}', 'UserController@update');
    $router->post('users/avatar', 'UserController@updateAvatar');
    $router->delete('users/{id}', 'UserController@delete');
    $router->get('user/profile', 'UserController@getProfile');

    // Product routes
    $router->get('/products', 'ProductController@index');
    $router->get('/products/{id}', 'ProductController@show');
    $router->post('/products', 'ProductController@store');
    $router->put('/products/{id}', 'ProductController@update');
    $router->delete('/products/{id}', 'ProductController@destroy');
    $router->post('/products/view-count', 'ProductController@updateViewCount');
    $router->get('/products/{productId}/variants', 'ProductController@getVariants');

    // Review routes
    $router->get('/products/{productId}/reviews', 'ReviewController@index');
    $router->post('/reviews', 'ReviewController@store');
    $router->put('/reviews/{id}', 'ReviewController@update');
    $router->delete('/reviews/{id}', 'ReviewController@destroy');
    $router->get('/users/{userId}/reviews', 'ReviewController@userReviews');

    // Order routes
    $router->get('/orders', 'OrderController@index');
    $router->get('/orders/{id}', 'OrderController@show');
    $router->post('/orders', 'OrderController@store');
    $router->get('/users/{userId}/orders', 'OrderController@getUserOrders');
    $router->put('/orders/{id}/status', 'OrderController@updateStatus');
    $router->get('/users/{userId}/purchase-history', 'OrderController@getPurchaseHistory');
    $router->post('/orders/reorder', 'OrderController@reorder');

    // Payment routes
    $router->post('/payments/create', 'PaymentController@createPayment');
    $router->post('/payments/notification', ['uses' => 'PaymentController@handleNotification', 'as' => 'payment.notification']);
    $router->get('/payments/status/{orderId}', 'PaymentController@checkStatus');

    // Shipping routes
    $router->get('/shipping/provinces', 'RajaOngkirController@getProvinces');
    $router->get('/shipping/cities', 'RajaOngkirController@getCities');
    $router->post('/shipping/calculate', 'RajaOngkirController@calculateShipping');

    // Banner routes
    $router->get('/banners', 'BannerController@index');
    $router->post('/banners', 'BannerController@store');
    $router->put('/banners/{id}', 'BannerController@update');
    $router->delete('/banners/{id}', 'BannerController@destroy');

    // Wishlist routes
    $router->get('/wishlist', 'WishlistController@index');
    $router->post('/wishlist', 'WishlistController@store');
    $router->delete('/wishlist', 'WishlistController@destroy');
    $router->get('/wishlist/check', 'WishlistController@check');

    // Cart routes
    $router->get('/cart/{user_id}', 'CartController@index');
    $router->post('/cart', 'CartController@store');
    $router->put('/cart/{id}', 'CartController@update');
    $router->delete('/cart/{id}', 'CartController@destroy');
    $router->delete('/cart/clear/{user_id}', 'CartController@clearCart');

    // Coupon routes
    $router->get('/coupons', 'CouponController@index');
    $router->post('/coupons', 'CouponController@store');
    $router->post('/coupons/validate', 'CouponController@validate_coupon');
    $router->put('/coupons/{id}', 'CouponController@update');
    $router->delete('/coupons/{id}', 'CouponController@destroy');

    // Admin routes
    $router->group(['prefix' => 'admin'], function () use ($router) {
        $router->get('products', 'AdminController@products');
        $router->get('orders', 'AdminController@orders');
        $router->get('users', 'AdminController@users');
        $router->get('banners', 'AdminController@banners');
        $router->get('coupons', 'AdminController@coupons');
    });
});
