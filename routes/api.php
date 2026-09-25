<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyCollectionController;
use App\Http\Controllers\ShopifyMenuController;

// 1. Bulk Collections Creation Route
Route::post('/create-collections', [ShopifyCollectionController::class, 'bulkCreate']);

// 2. Fetch Existing Collections Auto Route
Route::get('/get-collections', [ShopifyCollectionController::class, 'getCollections']);

// 3. Header / Navigation Menu Creation Route
Route::post('/create-menu', [ShopifyMenuController::class, 'createMenu']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
