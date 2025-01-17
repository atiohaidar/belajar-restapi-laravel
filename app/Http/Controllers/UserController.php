<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Str;

class UserController extends Controller
{
    private function userMustNotExist(string $username)
    {
        $user = User::where("username", $username)->count() > 0;
        if ($user) {
            //  user nya engga boleh ada sebelumnya
            throw new HttpResponseException(response([
                "errors" => [
                    "username" => ["Username has exist"]
                ]
            ])->setStatusCode(400));
        }
        return true;
    }
    private function checkUsernameOrPassword(User $user = null, $data = null)
    {
        
        if (!$user || !Hash::check($data["password"], $user->password)) {
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => ["username or password wrong"]
                ]
            ])->setStatusCode(401));
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
    public function login(UserLoginRequest $request): UserResource
    {
        $data = $request->validated();
        $user = User::where("username", $data["username"])->first();
        $this->checkUsernameOrPassword($user, $data);
        $user->token = Str::uuid()->toString();
        $user->save();
        return new UserResource($user);
    }

}
