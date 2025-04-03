<?php

use App\Http\Controllers\Api\AgencyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintCategoryController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;



// Authentication Routes
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/register', [AuthController::class, 'register'])->name('api.register');

// Routes requiring authentication (Sanctum middleware)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user'])->name('api.user');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::apiResource('complaints', ComplaintController::class);
        Route::apiResource('complaint-categories', ComplaintCategoryController::class);
        Route::apiResource('agencies', AgencyController::class);
        // Other protected routes will go inside this group
        Route::apiResource('users', UserController::class);

    });

Route::get("/send-email", function(Request $request) {
    $data = [
"nama"=> "ips",
        "imail"=> $request->query("email"),
        "ini_pesannya"=> " dari laravel",
    ];
    dispatch(new \App\Jobs\SendEmailJob($data));

    
    return response()->json(['message' => 'Email sent successfully' ]);
});