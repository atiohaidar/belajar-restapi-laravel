<?php

use Illuminate\Support\Facades\Route;
use Modules\Faq\app\Http\Controllers\API\FaqController;

// Public routes (no authentication required)

Route::get('faqs', [FaqController::class, 'index']);
Route::get('faqs/categories', [FaqController::class, 'categories']);
Route::get('faqs/{id}', [FaqController::class, 'show']);
// Protected routes (authentication required)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('faqs', [FaqController::class, 'store']);
    Route::put('faqs/{id}', [FaqController::class, 'update']);
    Route::delete('faqs/{id}', [FaqController::class, 'destroy']);
    Route::get('faqs.search/', [FaqController::class, 'search']);
});
