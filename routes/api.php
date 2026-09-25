<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyCollectionController;

Route::post('/create-collections', [ShopifyCollectionController::class, 'bulkCreate']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
