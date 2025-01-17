<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Str;
use Symfony\Component\HttpFoundation\JsonResponse;

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
    public function register(UserRegisterRequest $request): JsonResponse
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
    public function getUser(Request $request): UserResource{
        $user = Auth::user();
        return new UserResource($user);
    }
    public function update(UserUpdateRequest $request){
        $user = Auth::user();
        $data = $request->validated();
        if (isset($data["name"])){
            $user->name = $data["name"];
        }
        if (isset($data["password"])){
            $user->password = Hash::make($data["password"]);
        }
        $user->save();
        return (new UserResource($user));
    }
    public function logout(Request $request): JsonResponse{
        $user = Auth::user();
        $user->token = null; //lebih ke nge hapus token nya aja
        $user->save();
        return response()->json(["data"=>true],200);
    }

}
