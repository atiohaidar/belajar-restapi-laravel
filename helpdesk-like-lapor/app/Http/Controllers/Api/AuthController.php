<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Handle user login and issue API token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            // Allow login with username or email
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'], // Identify the device/token
        ]);

        $user = User::where('username', $request->login)
                    ->orWhere('email', $request->login)
                    ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => [__('auth.failed')], // Use standard translation key
            ]);
        }

        // Optionally revoke old tokens for the same device name if needed
        // $user->tokens()->where('name', $request->device_name)->delete();

        $token = $user->createToken($request->device_name)->plainTextToken;

        // Update last_login timestamp
        $user->forceFill(['last_login' => now()])->save();


        return response()->json(['token' => $token]);
    }
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'=> ['required', 'string', 'in:Admin,Agency Manager,Reporter'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role'=> $request->role,
        ]);

        return response()->json(['message' => 'User registered successfully'])->setStatusCode(201);
    }

    /**
     * Get the authenticated user's information.
     */
    public function user(Request $request): JsonResponse
    {
        // Load agency relationship if it exists
        $user = $request->user()->load('agency');
        return response()->json($user);
    }

    /**
     * Log the user out (revoke the token).
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::user()->tokens()->delete();
        // Revoke the token that was used to authenticate the current request...
        // Auth::user()->tokens()->delete();

        // $request->user()->currentAccessToken()->delete();
        // echo $user->id;
        // get token from user
        // $user->tokens()->where('id', $user->id)->delete();


        return response()->json(['message' => 'Logged out successfully']);
    }
}