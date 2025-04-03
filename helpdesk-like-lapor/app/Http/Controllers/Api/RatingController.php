<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRatingRequest;
use App\Http\Resources\RatingResource;
use App\Models\Agency; // Import Agency for potential filtering
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function __construct()
    {
        // Apply policy 
        // middleware (viewAny, view, delete handled by Policy)
         $this->authorizeResource(Rating::class, 'rating');
         // Note: 'create' authorization is handled within StoreRatingRequest authorize()
    }

    /**
     * Display a listing of the resource. (Admin only via policy)
     * Add filtering, e.g., by agency.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Rating::query()->with(['user:id,name', 'agency:id,name']); // Load basic info

        // Allow filtering by agency
        if ($request->filled('agency_id') && Agency::where('id', $request->agency_id)->exists()) {
            $query->where('agency_id', $request->agency_id);
        }

        // Filtering by stars?
        if($request->filled('stars')) {
            $query->where('stars', $request->integer('stars'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_dir', 'desc');
        if (in_array($sortBy, ['created_at', 'stars'])) {
             $query->orderBy($sortBy, $sortDirection);
         }

        $ratings = $query->paginate($request->input('per_page', 15));

        return RatingResource::collection($ratings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRatingRequest $request): JsonResponse
    {
        // Authorization handled by StoreRatingRequest using simplified policy
        $validated = $request->validated();
        $user = Auth::user();

        // --- ADD CHECK FOR EXISTING RATING HERE ---
        $existingRating = Rating::where('user_id', $user->id)
                                ->where('complaint_id', $validated['complaint_id'])
                                // ->where('agency_id', $validated['agency_id']) // Optional: check agency too
                                ->exists();

        if ($existingRating) {
            return response()->json(['message' => 'You have already rated this complaint.'], Response::HTTP_CONFLICT); // 409 Conflict
        }
        // --- END CHECK ---

        // Proceed to create if no existing rating found
        $rating = Rating::create([
            'user_id' => $user->id,
            'agency_id' => $validated['agency_id'],
            'complaint_id' => $validated['complaint_id'],
            'stars' => $validated['stars'],
            'review' => $validated['review'] ?? null,
        ]);

        $rating->load(['user', 'agency']);

        return (new RatingResource($rating))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
    }
    /**
     * Display the specified resource. (Handles view policy)
     */
    public function show(Rating $rating): RatingResource
    {
        // Authorization via authorizeResource
        $rating->load(['user:id,name', 'agency:id,name', 'complaint:id,title']); // Load relations
        return new RatingResource($rating);
    }


    /**
     * Remove the specified resource from storage. (Admin only via policy)
     */
    public function destroy(Rating $rating): Response
    {
        // Authorization via authorizeResource
        $rating->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }

     // --- Other standard resource methods (update) ---
     // Not implemented as ratings are immutable for users
     public function update(Request $request, Rating $rating) { abort(405); }
}