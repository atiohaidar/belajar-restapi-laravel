<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return User::with('roles')->get();
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $role = Role::where('name', $request->role)->first();
        $user->roles()->syncWithoutDetaching($role);

        return response()->json(['message' => 'Role assigned successfully']);
    }

    public function removeRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $role = Role::where('name', $request->role)->first();
        $user->roles()->detach($role);

        return response()->json(['message' => 'Role removed successfully']);
    }

    public function show(Request $request)
    {
        $user = $request->user();

        // Check if the user has the "admin" role
        if ($user->hasRole('admin')) {
            return User::with('roles')->get(); // Admin can see all users
        }

        // Non-admin users can only see their own details
        return $user->load('roles');
    }
}
