<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAgencyRequest;
use App\Http\Requests\Api\UpdateAgencyRequest;
use App\Http\Resources\AgencyResource;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AgencyController extends Controller
{
    public function __construct()
    {
        // Policy applied automatically via AuthServiceProvider mapping or manually here
         $this->authorizeResource(Agency::class, 'agency');
    }

    /**
     * Display a listing of the resource.
     * Allow filtering, e.g., only top-level agencies.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Agency::query();

        // Example Filter: only show top-level agencies
        if ($request->boolean('top_level_only')) {
            $query->whereNull('parent_id');
        }

        // Eager load relationships if requested? (Careful with deep loading)
        // if ($request->boolean('include_children')) {
        //     $query->with('childAgencies'); // Load immediate children
        // }

        $agencies = $query->orderBy('name')->get();
        return AgencyResource::collection($agencies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAgencyRequest $request): JsonResponse // Return JsonResponse for 201
    {
        $validated = $request->validated();
        $agency = Agency::create($validated);

        return (new AgencyResource($agency))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED); // 201 Created
    }

    /**
     * Display the specified resource.
     * Optionally load relationships.
     */
    public function show(Request $request, Agency $agency): AgencyResource
    {
         // Example: Load relationships based on query parameters
         if ($request->boolean('include_parent')) {
             $agency->load('parentAgency');
         }
         if ($request->boolean('include_children')) {
            // Maybe limit depth or use recursive resource if needed for full tree
             $agency->load('childAgencies');
         }

        return new AgencyResource($agency);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAgencyRequest $request, Agency $agency): AgencyResource
    {
        $validated = $request->validated();
        $agency->update($validated);
        return new AgencyResource($agency->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agency $agency): Response
    {
        // Add checks before deletion (e.g., has users, has complaints, has children)
        if ($agency->users()->exists()) {
            return response(['message' => 'Cannot delete agency with assigned users.'], Response::HTTP_CONFLICT);
        }
        if ($agency->complaints()->exists()) {
             return response(['message' => 'Cannot delete agency with assigned complaints.'], Response::HTTP_CONFLICT);
         }
         if ($agency->childAgencies()->exists()) {
             return response(['message' => 'Cannot delete agency with sub-agencies.'], Response::HTTP_CONFLICT);
         }
        // Add checks for transfers, followups, ratings if strict deletion required

        $agency->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}