<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreComplaintFollowUpRequest;
use App\Http\Resources\ComplaintFollowUpResource;
use App\Models\Complaint;
use App\Models\ComplaintFollowUp;
use App\Services\ComplaintService; // For logging
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ComplaintFollowUpController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
        // Authorization for 'destroy' handled via policy middleware/authorizeResource
        // $this->authorizeResource(ComplaintFollowUp::class, 'complaint_follow_up');
    }

    /**
     * Store a newly created follow-up for a specific complaint.
     * This is not part of authorizeResource, handle authorization via Request or middleware.
     */
    public function store(StoreComplaintFollowUpRequest $request, Complaint $complaint): JsonResponse
    {
        // Authorization handled by StoreComplaintFollowUpRequest
        
        $this->authorize('create', [ComplaintFollowUp::class, $complaint]); // Pass both class and complaint

        $validated = $request->validated();
        $user = Auth::user();
        // print_r($user->toArray());
        $followUp = $complaint->followUps()->create([
            'user_id' => $user->id,
            'description' => $validated['description'],
            // Set agency_id to the current user's agency if they are a manager?
            'agency_id' => ($user->role === 'Agency Manager') ? $user->agency_id : null,
        ]);

        $followUp->load('user'); // Load user for the resource

        // Add log entry to the complaint
        $this->complaintService->addLog($complaint, $user, 'Follow-up Added');

        return (new ComplaintFollowUpResource($followUp))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
    }


    /**
     * Remove the specified follow-up from storage.
     * (This corresponds to DELETE /complaint-follow-ups/{complaint_follow_up})
     */
    public function destroy(ComplaintFollowUp $complaintFollowUp): Response
    {
                // $this->authorize('delete'); // Pass both class and complaint
                $this->authorize('delete', [ComplaintFollowUp::class]); // Pass both class and complaint

        // Authorization handled by authorizeResource -> ComplaintFollowUpPolicy@delete
        $complaint = $complaintFollowUp->complaint; // Get context
        $user = Auth::user(); // User deleting

        $complaintFollowUp->delete();

        // Add log? Maybe not necessary to log deletion of internal follow-up unless required
        // if ($complaint) {
        //      $this->complaintService->addLog($complaint, $user, 'Follow-up Deleted', "Follow-up record deleted.");
        // }

        return response(null, Response::HTTP_NO_CONTENT);
    }

     // --- Other standard resource methods (index, show, update) ---
     // Typically not needed for follow-ups via dedicated endpoints.
     public function index() { abort(405); }
     public function show(ComplaintFollowUp $complaintFollowUp) { abort(405); }
     public function update(Request $request, ComplaintFollowUp $complaintFollowUp) { abort(405); }
}