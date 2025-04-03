<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreComplaintCategoryRequest;
use App\Http\Requests\Api\UpdateComplaintCategoryRequest;
use App\Http\Resources\ComplaintCategoryResource;
use App\Models\ComplaintCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response; // For status codes

class ComplaintCategoryController extends Controller
{
    /**
     * Instantiate a new controller instance.
     * Apply policy middleware.
     */
    public function __construct()
    {
        // Apply policy middleware to controller methods
        // Maps policy methods (viewAny, view, create, update, delete) to controller actions
        $this->authorizeResource(ComplaintCategory::class, 'complaint_category');

         // Alternatively, apply middleware per method in constructor or routes:
         // $this->middleware('can:viewAny,App\Models\ComplaintCategory')->only('index');
         // $this->middleware('can:create,App\Models\ComplaintCategory')->only('store');
         // etc.
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        // Authorization handled by authorizeResource / policy viewAny method
        $categories = ComplaintCategory::orderBy('name')->get();
        return ComplaintCategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreComplaintCategoryRequest $request): ComplaintCategoryResource
    {
         // Authorization handled by authorizeResource / policy create method
         // Validation handled by StoreComplaintCategoryRequest

        $validated = $request->validated();
        $category = ComplaintCategory::create($validated);

        return new ComplaintCategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(ComplaintCategory $complaintCategory): ComplaintCategoryResource
    {
        // Authorization handled by authorizeResource / policy view method
        // Route model binding finds the category or throws 404
        return new ComplaintCategoryResource($complaintCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateComplaintCategoryRequest $request, ComplaintCategory $complaintCategory): ComplaintCategoryResource
    {
        // Authorization handled by authorizeResource / policy update method
        // Validation handled by UpdateComplaintCategoryRequest

        $validated = $request->validated();
        $complaintCategory->update($validated);

        return new ComplaintCategoryResource($complaintCategory->fresh()); // Return updated resource
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ComplaintCategory $complaintCategory)
    {
         // Authorization handled by authorizeResource / policy delete method

        // Check if category is in use before deleting (optional, good practice)
        if ($complaintCategory->complaints()->exists()) {
             return response([
                 'message' => 'Cannot delete category because it is linked to existing complaints.'
             ], Response::HTTP_CONFLICT); // 409 Conflict
         }

        $complaintCategory->delete();

        return response(null, Response::HTTP_NO_CONTENT); // 204 No Content
    }
}