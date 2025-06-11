<?php

use App\Core\Router;
use App\Modules\Auth\Controllers\AuthController;

/** @var Router $router */

// Guest routes
$router->group(['middleware' => []], function (Router $router) {
    // Login
    $router->get('/login', [AuthController::class, 'showLoginForm']);
    $router->post('/login', [AuthController::class, 'login']);
    
    // Registration
    $router->get('/register', [AuthController::class, 'showRegisterForm']);
    $router->post('/register', [AuthController::class, 'register']);
    
    // Password reset
    $router->get('/password/reset', [AuthController::class, 'showResetForm']);
    $router->post('/password/email', [AuthController::class, 'sendResetEmail']);
    $router->get('/password/reset/{token}', [AuthController::class, 'showResetPasswordForm']);
    $router->post('/password/reset', [AuthController::class, 'resetPassword']);
});

// Authenticated routes
$router->group(['middleware' => [\App\Core\Middleware\AuthMiddleware::class]], function (Router $router) {
    // Logout
    $router->post('/logout', [AuthController::class, 'logout']);
    
    // Dashboard
    $router->get('/dashboard', [AuthController::class, 'dashboard']);
    
    // Profile
    $router->get('/profile', [AuthController::class, 'profile']);
    $router->put('/profile', [AuthController::class, 'updateProfile']);
    $router->put('/profile/password', [AuthController::class, 'updatePassword']);
});