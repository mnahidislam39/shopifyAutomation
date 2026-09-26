<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyCollectionController;
use App\Http\Controllers\ShopifyMenuController;
use App\Http\Controllers\ShopifyPageController;
use App\Http\Controllers\ShopifyBlogPostController;

// 1. Bulk Collections Creation Route
Route::post('/create-collections', [ShopifyCollectionController::class, 'bulkCreate']);

// 2. Fetch Existing Collections Auto Route
Route::get('/get-collections', [ShopifyMenuController::class, 'getCollections']);

// 3. Bulk Navigation Menu Creation Route
Route::post('/create-menu', [ShopifyMenuController::class, 'createMenu']);

// 4. Bulk Pages Creation Route
Route::post('/shopify/pages/bulk-create', [ShopifyPageController::class, 'createPages']);

// 5. Bulk Blog Posts Creation Route
Route::post('/shopify/bulk-blog-posts', [ShopifyBlogPostController::class, 'createBlogPosts']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
