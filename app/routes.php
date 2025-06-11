<?php

use App\Core\Router;

/** @var Router $router */

// Home page
$router->get('/', function () {
    if (auth()) {
        return redirect('/dashboard');
    }
    return view('welcome');
});

// API routes
$router->group(['prefix' => 'api'], function (Router $router) {
    // Health check
    $router->get('/health', function () {
        return json([
            'status' => 'ok',
            'timestamp' => now()
        ]);
    });
});

// Fallback route
$router->any('{any}', function () {
    http_response_code(404);
    return view('errors.404');
})->where('any', '.*');