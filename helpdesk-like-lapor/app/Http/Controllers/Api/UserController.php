<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUserRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash; // Import Hash facade
use Illuminate\Support\Facades\DB; // Import DB for transaction

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()->with('agency'); // Eager load agency

        // Filtering examples
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('username', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name'); // Default sort by name
        $sortDirection = $request->input('sort_dir', 'asc'); // Default direction
        if (in_array($sortBy, ['name', 'username', 'email', 'role', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }


        $users = $query->paginate($request->input('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $userData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
                // Set agency_id only if role is Agency Manager, otherwise null
                'agency_id' => ($validated['role'] === 'Agency Manager' && isset($validated['agency_id']))
                               ? $validated['agency_id']
                               : null,
            ];
            return User::create($userData);
        });

        return (new UserResource($user->load('agency'))) // Load agency for response
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user->load('agency')); // Eager load agency
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $validated = $request->validated();

        // Prevent admin from changing their own role/agency via this endpoint? (Optional: Policy might be enough)
        // if (Auth::id() === $user->id) {
        //     if (isset($validated['role']) && $validated['role'] !== $user->role) {
        //         abort(403, 'Admins cannot change their own role via this endpoint.');
        //     }
        // }

        DB::transaction(function () use ($user, $validated) {
            $updateData = $validated;

            // Hash password only if it's provided and not empty
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            } else {
                unset($updateData['password']); // Don't update password if empty
            }

            // Ensure agency_id is nullified if role changes from Agency Manager
             if (isset($updateData['role']) && $updateData['role'] !== 'Agency Manager') {
                 $updateData['agency_id'] = null;
             } elseif (isset($updateData['role']) && $updateData['role'] === 'Agency Manager' && !isset($updateData['agency_id'])) {
                 // This case should be caught by validation, but defensive check
                 // You might need to fetch the required agency_id from $validated if it exists
                  $updateData['agency_id'] = $validated['agency_id'] ?? null; // Ensure it's set if role is Manager
             } elseif (!isset($updateData['role']) && $user->role === 'Agency Manager' && array_key_exists('agency_id', $updateData) && $updateData['agency_id'] === null){
                 // If role isn't changing BUT they are trying to set agency_id to null for manager -> error?
                 // Validation should handle this via required_if
             }


            $user->update($updateData);
        });


        return new UserResource($user->fresh()->load('agency'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): Response
    {
        // Policy already prevents self-deletion.

        // Optional: Add further checks? e.g., cannot delete user with active complaints?
        // Deletion constraints in DB should handle relationships set to SET NULL or CASCADE.
        // If RESTRICT is used, deletion will fail automatically.

        // Example Check (if FKs don't handle this):
        // if ($user->complaints()->whereNotIn('status', ['Resolved', 'Archived'])->exists()) {
        //     return response(['message' => 'Cannot delete user with active complaints.'], Response::HTTP_CONFLICT);
        // }

        $user->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}