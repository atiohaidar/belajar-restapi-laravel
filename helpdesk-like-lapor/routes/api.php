<?php

use App\Http\Controllers\Api\AgencyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintCategoryController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Routes requiring authentication (Sanctum middleware)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user'])->name('api.user');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::apiResource('complaint-categories', ComplaintCategoryController::class);
        Route::apiResource('agencies', AgencyController::class);

        // Other protected routes will go inside this group
        Route::apiResource('users', UserController::class);
    });