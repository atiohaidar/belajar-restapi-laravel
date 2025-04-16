<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Role;
use App\Models\User;
use Modules\Ticketing\app\Models\Ticket;
use Modules\Ticketing\app\Models\TicketComment;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;
    protected User $user;
    protected string $adminToken;
    protected string $userToken;
    protected Ticket $ticket;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        // Create an admin user
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;

        // Create a regular user
        $this->user = User::factory()->create();
        $this->user->roles()->attach($userRole);
        $this->userToken = $this->user->createToken('user-token')->plainTextToken;

        // Create a ticket for testing comments
        $this->ticket = Ticket::create([
            'title' => 'Test Ticket',
            'description' => 'Test ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'general',
            'user_id' => $this->user->id
        ]);
    }

    /**
     * Test listing comments for a ticket.
     */
    public function test_list_comments_for_ticket(): void
    {
        // Create comments for the ticket
        TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'comment' => 'First comment'
        ]);

        TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->admin->id,
            'comment' => 'Admin response'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/tickets/' . $this->ticket->id . '/comments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'ticket_id',
                        'user_id',
                        'comment',
                        'created_at',
                        'updated_at',
                        'user' => [
                            'id',
                            'name'
                        ]
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test adding a comment to a ticket.
     */
    public function test_user_can_add_comment_to_own_ticket(): void
    {
        $commentData = [
            'comment' => 'This is a test comment'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/tickets/' . $this->ticket->id . '/comments', $commentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'ticket_id',
                    'user_id',
                    'comment',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'comment' => 'This is a test comment'
        ]);
    }

    /**
     * Test admin can comment on any ticket.
     */
    public function test_admin_can_comment_on_any_ticket(): void
    {
        $commentData = [
            'comment' => 'Admin comment on ticket'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/tickets/' . $this->ticket->id . '/comments', $commentData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->admin->id,
            'comment' => 'Admin comment on ticket'
        ]);
    }

    /**
     * Test viewing a specific comment.
     */
    public function test_view_specific_comment(): void
    {
        $comment = TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'comment' => 'Comment to view'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/tickets/' . $this->ticket->id . '/comments/' . $comment->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'ticket_id',
                    'user_id',
                    'comment',
                    'created_at',
                    'updated_at',
                    'user' => [
                        'id',
                        'name'
                    ]
                ]
            ]);
    }

    /**
     * Test updating a comment.
     */
    public function test_user_can_update_own_comment(): void
    {
        $comment = TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'comment' => 'Original comment'
        ]);

        $updateData = [
            'comment' => 'Updated comment'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/api/tickets/' . $this->ticket->id . '/comments/' . $comment->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Comment updated successfully'
            ]);

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id,
            'comment' => 'Updated comment'
        ]);
    }

    /**
     * Test user cannot update another user's comment.
     */
    public function test_user_cannot_update_another_users_comment(): void
    {
        $comment = TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->admin->id,
            'comment' => 'Admin comment'
        ]);

        $updateData = [
            'comment' => 'Trying to update admin comment'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/api/tickets/' . $this->ticket->id . '/comments/' . $comment->id, $updateData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('ticket_comments', [
            'id' => $comment->id,
            'comment' => 'Trying to update admin comment'
        ]);
    }

    /**
     * Test admin can update any comment.
     */
    public function test_admin_can_update_any_comment(): void
    {
        $comment = TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'comment' => 'User comment'
        ]);

        $updateData = [
            'comment' => 'Admin edited this comment'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/tickets/' . $this->ticket->id . '/comments/' . $comment->id, $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id,
            'comment' => 'Admin edited this comment'
        ]);
    }

    /**
     * Test deleting a comment.
     */
    public function test_user_can_delete_own_comment(): void
    {
        $comment = TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'comment' => 'Comment to delete'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/api/tickets/' . $this->ticket->id . '/comments/' . $comment->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Comment deleted successfully'
            ]);

        $this->assertDatabaseMissing('ticket_comments', [
            'id' => $comment->id
        ]);
    }

    /**
     * Test user cannot delete another user's comment.
     */
    public function test_user_cannot_delete_another_users_comment(): void
    {
        $comment = TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->admin->id,
            'comment' => 'Admin comment'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/api/tickets/' . $this->ticket->id . '/comments/' . $comment->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id
        ]);
    }

    /**
     * Test admin can delete any comment.
     */
    public function test_admin_can_delete_any_comment(): void
    {
        $comment = TicketComment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'comment' => 'User comment'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/tickets/' . $this->ticket->id . '/comments/' . $comment->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('ticket_comments', [
            'id' => $comment->id
        ]);
    }
}
