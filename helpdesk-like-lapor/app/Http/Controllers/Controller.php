<?php

namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    // alasan kenapa make ini
    // karena kita mau authorize resource di controller
    // https://medium.com/@alexmlndz1u/how-to-use-resource-policies-authorizeresource-in-laravel-11-6ab5646103df
}
