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