<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::resource('faq', FaqController::class)->names('faq');
// });

