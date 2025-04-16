<?php

namespace Modules\Ticketing\app\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Ticketing\app\Models\Ticket;
use Modules\Ticketing\app\Models\TicketComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;

class TicketCommentController extends Controller
{
    /**
     * Get all comments for a ticket.
     * 
     * @param int $ticketId
     * @return Response
     */
    public function index($ticketId)
    {
        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('viewAny', [TicketComment::class, $ticket]);

        $query = TicketComment::with('user:id,name')
            ->where('ticket_id', $ticketId);
            
        // Hide private comments for non-admin users
        if (!Auth::user()->hasRole('admin')) {
            $query->where(function($q) {
                $q->where('is_private', false)
                  ->orWhere('user_id', Auth::id());
            });
        }
        
        $comments = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $comments
        ]);
    }

    /**
     * Store a newly created comment for a ticket.
     * 
     * @param Request $request
     * @param int $ticketId
     * @return Response
     */
    public function store(Request $request, $ticketId)
    {
        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('create', [TicketComment::class, $ticket]);

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
            'is_private' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Only admins can create private comments
        $isPrivate = $request->is_private ?? false;
        if ($isPrivate && !Auth::user()->hasRole('admin')) {
            $isPrivate = false;
        }

        $comment = TicketComment::create([
            'ticket_id' => $ticketId,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
            'is_private' => $isPrivate
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Comment added successfully',
            'data' => $comment->load('user:id,name')
        ], 201);
    }

    /**
     * Display the specified comment.
     * 
     * @param int $ticketId
     * @param int $commentId
     * @return Response
     */
    public function show($ticketId, $commentId)
    {
        $comment = TicketComment::with('user:id,name')
            ->where('ticket_id', $ticketId)
            ->where('id', $commentId)
            ->first();

        if (!$comment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Comment not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('view', $comment);

        return response()->json([
            'status' => 'success',
            'data' => $comment
        ]);
    }

    /**
     * Update the specified comment.
     * 
     * @param Request $request
     * @param int $ticketId
     * @param int $commentId
     * @return Response
     */
    public function update(Request $request, $ticketId, $commentId)
    {
        $comment = TicketComment::where('ticket_id', $ticketId)
            ->where('id', $commentId)
            ->first();

        if (!$comment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Comment not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('update', $comment);

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
            'is_private' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Only allow changing the is_private flag if user is admin
        if (isset($request->is_private) && !Auth::user()->hasRole('admin')) {
            $request->merge(['is_private' => $comment->is_private]);
        }

        $comment->update($request->only(['comment', 'is_private']));

        return response()->json([
            'status' => 'success',
            'message' => 'Comment updated successfully',
            'data' => $comment
        ]);
    }

    /**
     * Remove the specified comment.
     * 
     * @param int $ticketId
     * @param int $commentId
     * @return Response
     */
    public function destroy($ticketId, $commentId)
    {
        $comment = TicketComment::where('ticket_id', $ticketId)
            ->where('id', $commentId)
            ->first();

        if (!$comment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Comment not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment deleted successfully'
        ]);
    }
}