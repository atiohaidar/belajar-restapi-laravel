<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Ticketing\app\Http\Controllers\API\TicketController;
use Modules\Ticketing\app\Http\Controllers\API\TicketCommentController;
use Modules\Ticketing\app\Http\Controllers\API\TicketAttachmentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function() {
    // Tickets routes
    Route::apiResource('tickets', TicketController::class);
    
    // Ticket read status routes
    Route::get('tickets/unread/count', [TicketController::class, 'unreadCount']);
    Route::put('tickets/{id}/mark-read', [TicketController::class, 'markRead']);
    Route::put('tickets/{id}/mark-unread', [TicketController::class, 'markUnread']);
    
    // Ticket comments routes
    Route::get('tickets/{ticketId}/comments', [TicketCommentController::class, 'index']);
    Route::post('tickets/{ticketId}/comments', [TicketCommentController::class, 'store']);
    Route::get('tickets/{ticketId}/comments/{commentId}', [TicketCommentController::class, 'show']);
    Route::put('tickets/{ticketId}/comments/{commentId}', [TicketCommentController::class, 'update']);
    Route::delete('tickets/{ticketId}/comments/{commentId}', [TicketCommentController::class, 'destroy']);

    // Ticket attachments routes
    Route::get('tickets/{ticketId}/attachments', [TicketAttachmentController::class, 'index']);
    Route::post('tickets/{ticketId}/attachments', [TicketAttachmentController::class, 'store']);
    Route::get('tickets/{ticketId}/attachments/{attachmentId}', [TicketAttachmentController::class, 'show']);
    Route::get('tickets/{ticketId}/attachments/{attachmentId}/download', [TicketAttachmentController::class, 'download']);
    Route::delete('tickets/{ticketId}/attachments/{attachmentId}', [TicketAttachmentController::class, 'destroy']);
});
