<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Hash;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private function userMustNotExist(string $username)
    {
        $user = User::where("username", $username)->count() > 0;
        if ($user) {
            //  user nya engga boleh ada sebelumnya
            throw new HttpResponseException(response([
                "errors" => [
                    "username" => "Username has exist"
                ]
            ]));
        }
        return true;
    }
    public function register(UserRegisterRequest $request)
    {
        $data = $request->validated();
        $this->userMustNotExist($data["username"]);
        $user = new User($data);
        $user->password = Hash::make($data["password"]);
        $user->save();
        return (new UserResource($user))->response()->setStatusCode(201);
    }
    
}
