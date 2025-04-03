<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Complaint; // Import Complaint if needed for context/logging
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use App\Services\ComplaintService; // Import ComplaintService for logging

class CommentController extends Controller
{
     protected ComplaintService $complaintService;

     public function __construct(ComplaintService $complaintService)
     {
         $this->complaintService = $complaintService;
         // Apply policy middleware for the 'destroy' method
         $this->authorizeResource(Comment::class, 'comment');
     }

    /**
     * Remove the specified comment from storage.
     * Note: This method is part of apiResource but we only implement destroy.
     * Consider if route should be specific like DELETE /comments/{comment}
     * or nested DELETE /complaints/{complaint}/comments/{comment}.
     * Let's assume stand-alone /comments/{comment} for simplicity here.
     */
    public function destroy(Comment $comment): Response
    {
        // Authorization handled by authorizeResource -> CommentPolicy@delete

        $complaint = $comment->complaint; // Get complaint for logging context
        $user = Auth::user();
        $commentAuthor = $comment->user; // Get comment author for logging details

        $comment->delete();

        // Log the deletion on the parent complaint's log
         if ($complaint) {
            $logDetails = "Comment by {$commentAuthor->name} deleted.";
             if ($user->id !== $commentAuthor->id) { // Log if someone else deleted it (Admin)
                 $logDetails .= " (Deleted by: {$user->name})";
             }
             $this->complaintService->addLog($complaint, $user, 'Comment Deleted', $logDetails);
         }


        return response(null, Response::HTTP_NO_CONTENT);
    }

    // --- Other standard resource methods (index, show, store, update) ---
    // We are not implementing these for comments via this controller,
    // as viewing is via ComplaintController@show and storing via ComplaintController@storeComment.
    // You can remove them or return a 405 Method Not Allowed if accessed.

     public function index() { abort(405); }
     public function store(Request $request) { abort(405); }
     public function show(Comment $comment) { abort(405); }
     public function update(Request $request, Comment $comment) { abort(405); }

}