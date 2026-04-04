<?php

use App\Core\Routing\Router;
use App\Controllers\ProductController;
use App\Controllers\CartController;
use App\Controllers\OrderController;
use App\Controllers\AdminProductController;
use App\Controllers\LanguageController;
use App\Controllers\ThemeController;
use App\Controllers\AdminThemeController;
use App\Controllers\AuthController;
use App\Controllers\SocialAuthController;
use App\Controllers\AdminController;

$router = new Router();

$router->get('/',[ProductController::class,'index']);
$router->get('/products',[ProductController::class,'index']);
$router->get('/product/{slug}',[ProductController::class,'show']);
$router->get('/category/{slug}',[ProductController::class,'showCategory']);
$router->get('/cart',[CartController::class,'index']);
$router->post('/cart/add',[CartController::class,'add']);
$router->get('/cart/add/{id}',[CartController::class,'addByGet']);
$router->get('/checkout',[OrderController::class,'checkout']);
$router->post('/place-order',[OrderController::class,'placeOrder']);

// Перемикання мови
$router->get('/language/{lang}', [LanguageController::class, 'switch']);

// Перемикання тем
$router->get('/themes', [ThemeController::class, 'index']);
$router->get('/theme/switch/{theme}', [ThemeController::class, 'switch']);

// Автентифікація
$router->get('/login',[AuthController::class,'showLogin']);
$router->post('/login',[AuthController::class,'login']);
$router->get('/register',[AuthController::class,'showRegister']);
$router->post('/register',[AuthController::class,'register']);
$router->get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);
$router->get('/logout',[AuthController::class,'logout']);
$router->get('/forgot-password',[AuthController::class,'showForgotPassword']);
$router->post('/forgot-password',[AuthController::class,'forgotPassword']);
$router->get('/reset-password/{token}',[AuthController::class,'showResetPassword']);
$router->post('/reset-password',[AuthController::class,'resetPassword']);

// Профіль користувача
$router->get('/profile', [AuthController::class, 'showProfile']);

// Соціальна автентифікація
$router->get('/auth/google', [SocialAuthController::class, 'redirectToGoogle']);
$router->get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
$router->get('/auth/facebook', [SocialAuthController::class, 'redirectToFacebook']);
$router->get('/auth/facebook/callback', [SocialAuthController::class, 'handleFacebookCallback']);

// Адміністративна панель
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/settings', [AdminController::class, 'settings']);
$router->post('/admin/settings/save', [AdminController::class, 'saveSettings']);

// Управління товарами в адмінці
$router->get('/admin/products', [AdminProductController::class, 'index']);
$router->get('/admin/products/create', [AdminProductController::class, 'create']);
$router->post('/admin/products/store', [AdminProductController::class, 'store']);

// Управління темами в адмінці
$router->get('/admin/themes', [AdminThemeController::class, 'index']);
