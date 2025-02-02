<?php

use App\Http\Controllers\AddressController;
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

Route::post("/register", [UserController::class, "register"])->name("user.register");
Route::post("/login", [UserController::class, "login"])->name("user.login");
Route::middleware(\App\Http\Middleware\ApiAuthMiddleware::class)->group(function () {
    Route::get("/users/current", [UserController::class, "getUser"]);
    Route::patch("/users/current", [UserController::class, "update"])->name("user.update");
    Route::delete("/users/logout", [UserController::class, "logout"]);
    Route::prefix("/contacts")->group(function () {
        Route::post("/", [ContactController::class, "create"])->name("contact.create");
        Route::put("/{id}", [ContactController::class, "update"])->name("contact.update");
        Route::get("/{id}", [ContactController::class, "get"])->name("contact.get");
        Route::delete("/{id}", [ContactController::class, "delete"])->name("contact.delete");
        Route::get("/", [ContactController::class, "list"])->name("contact.list");
        Route::prefix("/{idContact}/addresses")->group(function () {
            Route::post("/", [AddressController::class, "create"])->name("address.create");
            Route::get("/{idAddress}", [AddressController::class, "get"])->name("address.get");
            Route::put("/{idAddress}", [AddressController::class, "update"])->name("address.update");
            Route::delete("/{idAddress}", [AddressController::class, "delete"])->name("address.delete");
            Route::get("/", [AddressController::class, "list"])->name("address.list"); 
        });
    });
});
