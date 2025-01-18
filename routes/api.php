<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post("/register", [UserController::class,"register"])->name("user.register");
Route::post("/login", [UserController::class,"login"])->name("user.login");
Route::middleware(\App\Http\Middleware\ApiAuthMiddleware::class)->group(function () {
    Route::get("/users/current", [UserController::class,"getUser"]);
    Route::patch("/users/current", [UserController::class,"update"])->name("user.update");
    Route::delete("/users/logout", [UserController::class,"logout"]);
    Route::prefix("/contacts")->group(function () {
        Route::post("/", [ContactController::class,"create"])->name("contact.create");
        Route::put("/{id}", [ContactController::class,"update"])->name("contact.update");
    });
});
