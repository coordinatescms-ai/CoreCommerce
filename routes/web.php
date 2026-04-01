<?php
use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Controllers\CartController;
use App\Controllers\AuthController;
use App\Controllers\CheckoutController;
use App\Controllers\AdminProductController;
use App\Controllers\LanguageController;
use App\Controllers\ThemeController;
use App\Controllers\AdminThemeController;
use App\Controllers\PublicThemeSwitcherController;
use App\Controllers\RedirectController;
use App\Controllers\SocialAuthController;

$router->get('/themes', [PublicThemeSwitcherController::class, 'show']);
$router->get('/admin/themes', [AdminThemeController::class, 'index']);

$router->get('/admin/products', [AdminProductController::class, 'index']);
$router->get('/admin/products/create', [AdminProductController::class, 'create']);
$router->post('/admin/products/store', [AdminProductController::class, 'store']);
$router->get('/admin/products/delete/{id}', [AdminProductController::class, 'delete']);

$router->get('/',[HomeController::class,'index']);
$router->get('/products',[ProductController::class,'index']);
$router->get('/product/{slug}',[ProductController::class,'show']);
$router->get('/category/{slug}',[ProductController::class,'showCategory']);

$router->get('/cart',[CartController::class,'index']);
$router->get('/cart/add/{id}',[CartController::class,'add']);
$router->get('/cart/remove/{id}',[CartController::class,'remove']);

$router->get('/login',[AuthController::class,'showLogin']);
$router->post('/login',[AuthController::class,'login']);
$router->get('/register',[AuthController::class,'showRegister']);
$router->post('/register',[AuthController::class,'register']);
$router->get('/logout',[AuthController::class,'logout']);
$router->get('/forgot-password',[AuthController::class,'showForgotPassword']);
$router->post('/forgot-password',[AuthController::class,'forgotPassword']);
$router->get('/reset-password/{token}',[AuthController::class,'showResetPassword']);
$router->post('/reset-password',[AuthController::class,'resetPassword']);

// Соціальна автентифікація
$router->get('/auth/google', [SocialAuthController::class, 'redirectToGoogle']);
$router->get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
$router->get('/auth/facebook', [SocialAuthController::class, 'redirectToFacebook']);
$router->get('/auth/facebook/callback', [SocialAuthController::class, 'handleFacebookCallback']);

// Профіль користувача
$router->get('/profile', [AuthController::class, 'showProfile']);

$router->get('/checkout',[CheckoutController::class,'index']);
$router->post('/checkout',[CheckoutController::class,'process']);

$router->get('/language/{lang}',[LanguageController::class,'change']);

$router->get('/api/themes',[ThemeController::class,'index']);
$router->get('/theme/{theme}',[ThemeController::class,'change']);
$router->get('/api/theme/info',[ThemeController::class,'info']);

// Адміністративні маршрути для редиректів
$router->get('/admin/redirects', [RedirectController::class, 'index']);
$router->get('/admin/redirects/deactivate/{id}', [RedirectController::class, 'deactivate']);
$router->get('/admin/redirects/delete/{id}', [RedirectController::class, 'delete']);
