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
    $router->addRoute(['GET', 'POST'], '/payments/notification', 'PaymentController@handleNotification');
    $router->addRoute(['GET', 'POST'], '/payments/notification/recurring', 'PaymentController@handleRecurringNotification');
    $router->get('/payments/status/{orderId}/user/{userId}', 'PaymentController@checkStatus');

    // Shipping routes
    $router->get('/shipping/provinces', 'RajaOngkirController@getProvinces');
    $router->get('/shipping/cities', 'RajaOngkirController@getCities');
    $router->get('/shipping/couriers', 'RajaOngkirController@getCouriers');
    $router->post('/shipping/calculate', 'RajaOngkirController@calculateShipping');
    $router->get('/shipping/tracking/{orderId}', 'RajaOngkirController@getShippingStatus');

    // Banner routes
    $router->get('/banners', 'BannerController@index');
    $router->post('/banners', 'BannerController@store');
    $router->put('/banners/{id}', 'BannerController@update');
    $router->delete('/banners/{id}', 'BannerController@destroy');

    // Wishlist routes
    $router->get('/users/{userId}/wishlist', 'WishlistController@index');
    $router->post('/users/{userId}/wishlist', 'WishlistController@store');
    $router->delete('/users/{userId}/wishlist/{productId}', 'WishlistController@destroy');
    $router->get('/users/{userId}/wishlist/check/{productId}', 'WishlistController@check');

    // Cart routes
    $router->get('/cart/{user_id}', 'CartController@index');

    // Notification routes
    $router->get('/notifications/{userId}', 'NotificationController@index');
    $router->get('/notifications/{userId}/unread', 'NotificationController@getUnread');
    $router->get('/notifications/{userId}/payments', 'NotificationController@getPaymentNotifications');
    $router->post('/notifications/mark-read', 'NotificationController@markAsRead');
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

    // Notification routes
    $router->get('/users/{userId}/notifications', 'NotificationController@index');
    $router->get('/users/{userId}/notifications/unread', 'NotificationController@getUnread');
    $router->post('/notifications/mark-read', 'NotificationController@markAsRead');

    // Admin routes
    $router->group(['prefix' => 'admin'], function () use ($router) {
        $router->get('products', 'AdminController@products');
        $router->get('orders', 'AdminController@orders');
        $router->get('users', 'AdminController@users');
        $router->get('banners', 'AdminController@banners');
        $router->get('coupons', 'AdminController@coupons');
    });

    // Password routes
    $router->post('user/change-password', 'UserController@changePassword');

    // User Address routes
    $router->get('/users/{userId}/addresses', 'UserAddressController@index');
    $router->post('/users/{userId}/addresses', 'UserAddressController@store');
    $router->put('/users/{userId}/addresses/{addressId}', 'UserAddressController@update');
    $router->delete('/users/{userId}/addresses/{addressId}', 'UserAddressController@destroy');
    $router->post('/users/{userId}/addresses/{addressId}/set-default', 'UserAddressController@setDefault');
});
