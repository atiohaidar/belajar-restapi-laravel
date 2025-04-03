<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommentRequest;
use App\Http\Requests\Api\StoreComplaintRequest;
use App\Http\Requests\Api\TransferComplaintRequest;
use App\Http\Requests\Api\UpdateComplaintRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
// use App\Services\ComplaintService; // Import the service
use App\Services\ComplaintService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth; 
use Storage;// Import Auth facade

class ComplaintController extends Controller
{
    protected ComplaintService $complaintService; // Inject service

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
        // Apply policy using middleware for resource controller methods
        $this->authorizeResource(Complaint::class, 'complaint');
    }

    /**
     * Display a listing of the resource based on user role.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = Auth::user();
        $query = Complaint::query()->with([
            'user:id,name', // Select specific columns for performance
            'agency:id,name',
            'category:id,name'
        ]); // Eager load basic info

        // Role-based filtering
        if ($user->role === 'Reporter') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'Agency Manager') {
            // Simple: only direct agency
            $query->where('agency_id', $user->agency_id);
            // Complex: include sub-agencies (requires recursive CTE or package)
            // $agencyIds = $this->getAgencyHierarchyIds($user->agency_id);
            // $query->whereIn('agency_id', $agencyIds);
        }
        // Admin sees all - no extra where clause needed

        // General Filtering (add more as needed)
        if ($request->filled('status')) $query->where('status', $request->input('status'));
        if ($request->filled('priority')) $query->where('priority', $request->input('priority'));
        if ($request->filled('category_id')) $query->where('category_id', $request->input('category_id'));
        if ($request->filled('agency_id') && $user->role === 'Admin') $query->where('agency_id', $request->input('agency_id')); // Admin can filter by agency

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_dir', 'desc');
        if (in_array($sortBy, ['created_at', 'updated_at', 'status', 'priority', 'title'])) {
             $query->orderBy($sortBy, $sortDirection);
         } else {
             $query->orderBy('created_at', 'desc'); // Default sort
         }

        $complaints = $query->paginate($request->input('per_page', 15));

        // Use the static collection method to disable deep relationship loading
        return ComplaintResource::collection($complaints);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $validated = $request->validated(); // Validation + Authorization done by request class
        $user = Auth::user(); // Get the authenticated reporter

        try {
            $complaint = $this->complaintService->createComplaint($validated, $user);
            // Load necessary relations for the response resource
            $complaint->load(['user', 'category', 'attachments']);

            // Enable relationship loading for this single resource response
            ComplaintResource::$loadRelationships = true;
            return (new ComplaintResource($complaint))
                    ->response()
                    ->setStatusCode(Response::HTTP_CREATED);

        } catch (\Exception $e) {
            // Log the error details if needed
             \Log::error("Complaint creation failed: " . $e->getMessage(), ['user_id' => $user->id]);
            return response()->json(['message' => 'Failed to create complaint. An error occurred.'], 500);
        }
    }

    /**
     * Display the specified resource with all details.
     */
    public function show(Complaint $complaint): ComplaintResource
    {
        // Authorization handled by authorizeResource

        // Load all relevant relationships for the detail view
        $complaint->load([
            'user',
            'agency',
            'category',
            'attachments',
            'comments' => fn($q) => $q->with('user:id,name,role')->orderBy('created_at'), // Load comments with user info
            'logs' => fn($q) => $q->with('user:id,name,role')->orderBy('timestamp'), // Load logs with user info
            // 'followUps', 'transfers', 'ratings' // Add when implemented
        ]);

        // Ensure relationships are loaded in the resource
        ComplaintResource::$loadRelationships = true;
        return new ComplaintResource($complaint);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateComplaintRequest $request, Complaint $complaint): ComplaintResource|JsonResponse
    {
        // Authorization handled by request class and authorizeResource
        $validated = $request->validated();
        $updater = Auth::user();

        // Prevent invalid updates (e.g., Reporter trying to update status)
         if (empty($validated)) {
             // This might happen if the request rules filtered out all fields based on role
             return response()->json(['message' => 'No updatable fields provided or permitted for your role.'], 400);
         }


        try {
            $updatedComplaint = $this->complaintService->updateComplaint($complaint, $validated, $updater);
            // Load relations needed for the response
             $updatedComplaint->load(['user', 'category', 'agency']);

            ComplaintResource::$loadRelationships = true; // Ensure details shown in response
            return new ComplaintResource($updatedComplaint);

        } catch (\Exception $e) {
             \Log::error("Complaint update failed: " . $e->getMessage(), ['complaint_id' => $complaint->id, 'user_id' => $updater->id]);
             return response()->json(['message' => 'Failed to update complaint. An error occurred.'], 500);
         }
    }

    /**
     * Remove the specified resource from storage. (Or archive)
     */
    public function destroy(Complaint $complaint): Response
    {
        // Authorization handled by authorizeResource

        // Consider archiving instead of deleting:
        // $this->complaintService->archiveComplaint($complaint, Auth::user());
        // return response(null, Response::HTTP_NO_CONTENT);

        // Actual Deletion:
        try {
            DB::transaction(function() use ($complaint) {
                 // Manually delete attachments from storage first if cascade doesn't handle it
                 foreach ($complaint->attachments as $attachment) {
                    Storage::disk('public')->delete($attachment->file_path);
                 }
                 // Database cascade should handle deleting attachment records, comments, logs etc.
                 $complaint->delete();
            });
             // Optional: Log deletion action by Admin
            // $this->complaintService->addLog($complaint, Auth::user(), 'Deleted', 'Complaint permanently deleted.'); // Won't work as complaint is gone

            return response(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            \Log::error("Complaint deletion failed: " . $e->getMessage(), ['complaint_id' => $complaint->id]);
            // Check for foreign key constraints if deletion fails without checks
             return response()->json(['message' => 'Failed to delete complaint. It might be linked to other records.'], 500); // Or 409 Conflict
         }
    }

    // --- Helper for Hierarchy (Example - Implement fully if needed) ---
    // private function getAgencyHierarchyIds($agencyId): array
    // {
    //     // Use recursive CTE or a package like 'staudenmeir/laravel-adjacency-list'
    //     // Placeholder: only return the direct ID for now
    //     return [$agencyId];
    // }
    public function storeComment(StoreCommentRequest $request, Complaint $complaint): JsonResponse
    {
        // Authorization handled by StoreCommentRequest authorize() method

        $validated = $request->validated();
        $user = Auth::user();

        $comment = $complaint->comments()->create([
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        // Eager load user for the response resource
        $comment->load('user');

        // Optional: Add a log entry
        $this->complaintService->addLog($complaint, $user, 'Comment Added');

        // Optional: Dispatch event for notifications
        // event(new \App\Events\CommentAdded($comment));

        return (new CommentResource($comment))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
    }
    /**
     * Initiate a transfer for the specified complaint.
     */
    public function transfer(TransferComplaintRequest $request, Complaint $complaint): JsonResponse // Or ComplaintResource? TransferResource?
    {
        // Authorization handled by TransferComplaintRequest
        $validated = $request->validated();
        $initiator = Auth::user();

        try {
            $updatedComplaint = $this->complaintService->transferComplaint($complaint, $validated, $initiator);

            // What to return? The updated complaint or the transfer record?
            // Let's return the updated complaint resource for consistency with update endpoint
             $updatedComplaint->load(['user', 'category', 'agency']); // Load relations
             ComplaintResource::$loadRelationships = true;
             return new JsonResponse(new ComplaintResource($updatedComplaint), Response::HTTP_OK);

            // Alternative: Return the newly created transfer record
            // $transferRecord = $updatedComplaint->transfers()->latest()->first()->load(['user', 'fromAgency', 'toAgency']);
            // return new JsonResponse(new ComplaintTransferResource($transferRecord), Response::HTTP_OK);

        } catch (\Exception $e) {
             \Log::error("Complaint transfer failed: " . $e->getMessage(), ['complaint_id' => $complaint->id, 'user_id' => $initiator->id]);
             return response()->json(['message' => 'Failed to transfer complaint. An error occurred.'], 500);
         }
    }
}