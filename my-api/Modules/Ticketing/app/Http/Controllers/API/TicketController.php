<?php

namespace Modules\Ticketing\app\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Ticketing\app\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    /**
     * Display a listing of the tickets.
     * 
     * @return Response
     */
    public function index()
    {
        // Admin sees all tickets
        if (Auth::user()->hasRole('admin')) {
            $tickets = Ticket::with([
                'user:id,name', 
                'assignedTo:id,name',
                'readBy:id,name'
            ])->get();
        } else {
            // Normal users see only their own tickets
            $tickets = Ticket::with([
                'user:id,name', 
                'assignedTo:id,name',
                'readBy:id,name'
            ])
            ->where('user_id', Auth::id())
            ->get();
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $tickets
        ]);
    }

    /**
     * Store a newly created ticket in storage.
     * 
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        // Authorize the action
        Gate::authorize('create', Ticket::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'nullable|string|max:100',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = Ticket::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'category' => $request->category,
            'user_id' => Auth::id(),
            'assigned_to' => $request->assigned_to,
            'status' => 'open'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket created successfully',
            'data' => $ticket
        ], 201);
    }

    /**
     * Display the specified ticket.
     * 
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $ticket = Ticket::with([
            'user:id,name', 
            'assignedTo:id,name', 
            'readBy:id,name',
            'comments.user:id,name', 
            'attachments'
        ])->find($id);

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('view', $ticket);

        // Automatically mark as read for admin users
        if (Auth::user()->hasRole('admin') && !$ticket->is_read) {
            $this->markAsRead($ticket);
        }

        return response()->json([
            'status' => 'success',
            'data' => $ticket
        ]);
    }

    /**
     * Update the specified ticket in storage.
     * 
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:open,pending,in_progress,resolved,closed',
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'category' => 'nullable|string|max:100',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket updated successfully',
            'data' => $ticket
        ]);
    }

    /**
     * Remove the specified ticket from storage.
     * 
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('delete', $ticket);

        $ticket->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket deleted successfully'
        ]);
    }

    /**
     * Mark a ticket as read.
     * 
     * @param int $id
     * @return Response
     */
    public function markRead($id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('manageReadStatus', Ticket::class);

        $this->markAsRead($ticket);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket marked as read',
            'data' => $ticket
        ]);
    }

    /**
     * Mark a ticket as unread.
     * 
     * @param int $id
     * @return Response
     */
    public function markUnread($id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('manageReadStatus', Ticket::class);

        $ticket->update([
            'is_read' => false,
            'read_at' => null,
            'read_by' => null
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket marked as unread',
            'data' => $ticket
        ]);
    }

    /**
     * Get unread tickets count
     * 
     * @return Response
     */
    public function unreadCount()
    {
        // Only admins should be able to see the unread count
        Gate::authorize('manageReadStatus', Ticket::class);
        
        $count = Ticket::where('is_read', false)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'count' => $count
            ]
        ]);
    }

    /**
     * Helper method to mark a ticket as read
     * 
     * @param Ticket $ticket
     */
    private function markAsRead($ticket)
    {
        $ticket->update([
            'is_read' => true,
            'read_at' => now(),
            'read_by' => Auth::id()
        ]);
    }
}