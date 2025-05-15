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
    $router->post('users/{id}', 'UserController@update');
    $router->post('users/{id}/avatar', 'UserController@updateAvatar');
    $router->delete('users/{id}', 'UserController@delete');

    // Product routes
    $router->get('/products', 'ProductController@index');
    $router->get('/products/{id}', 'ProductController@show');
    $router->post('/products', 'ProductController@store');
    $router->put('/products/{id}', 'ProductController@update');
    $router->delete('/products/{id}', 'ProductController@destroy');
    $router->post('/products/view-count', 'ProductController@updateViewCount');
    $router->get('/products/{productId}/variants', 'ProductController@getVariants');

    // Order routes
    $router->get('/orders', 'OrderController@index');
    $router->get('/orders/{id}', 'OrderController@show');
    $router->post('/orders', 'OrderController@store');
    $router->get('/users/{userId}/orders', 'OrderController@getUserOrders');
    $router->put('/orders/{id}/status', 'OrderController@updateStatus');

    // Payment routes
    $router->post('/payments/create', 'PaymentController@createPayment');
    $router->post('/payments/notification', 'PaymentController@handleNotification');
    $router->get('/payments/status/{orderId}', 'PaymentController@checkStatus');

    // Shipping routes
    $router->get('/shipping/provinces', 'RajaOngkirController@getProvinces');
    $router->get('/shipping/cities', 'RajaOngkirController@getCities');
    $router->post('/shipping/calculate', 'RajaOngkirController@calculateShipping');
});
