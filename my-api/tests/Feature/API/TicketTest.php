<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Role;
use App\Models\User;
use Modules\Ticketing\app\Models\Ticket;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;
    protected User $user;
    protected string $adminToken;
    protected string $userToken;

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
    }

    /**
     * Test user can create a ticket.
     */
    public function test_user_can_create_ticket(): void
    {
        $ticketData = [
            'title' => 'Test Ticket',
            'description' => 'This is a test ticket description',
            'priority' => 'medium',
            'category' => 'general'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/tickets', $ticketData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'category',
                    'user_id',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('tickets', [
            'title' => 'Test Ticket',
            'user_id' => $this->user->id
        ]);
    }

    /**
     * Test listing all tickets.
     */
    public function test_admin_can_list_all_tickets(): void
    {
        // Create some test tickets
        $userTicket = Ticket::create([
            'title' => 'User Ticket',
            'description' => 'User ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'technical',
            'user_id' => $this->user->id
        ]);

        $adminTicket = Ticket::create([
            'title' => 'Admin Ticket',
            'description' => 'Admin ticket description',
            'status' => 'open',
            'priority' => 'high',
            'category' => 'billing',
            'user_id' => $this->admin->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'category',
                        'user_id',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test user can only see their own tickets.
     */
    public function test_user_can_only_see_own_tickets(): void
    {
        // Create tickets for both users
        $userTicket = Ticket::create([
            'title' => 'User Ticket',
            'description' => 'User ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'technical',
            'user_id' => $this->user->id
        ]);

        $adminTicket = Ticket::create([
            'title' => 'Admin Ticket',
            'description' => 'Admin ticket description',
            'status' => 'open',
            'priority' => 'high',
            'category' => 'billing',
            'user_id' => $this->admin->id
        ]);

        // User should only see their own tickets
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'title' => 'User Ticket',
                        'user_id' => $this->user->id
                    ]
                ]
            ]);
    }

    /**
     * Test viewing a specific ticket.
     */
    public function test_user_can_view_own_ticket(): void
    {
        // Create a ticket for the user
        $ticket = Ticket::create([
            'title' => 'User Ticket',
            'description' => 'User ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'technical',
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/tickets/' . $ticket->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'category',
                    'user_id',
                    'user',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test user cannot view another user's ticket.
     */
    public function test_user_cannot_view_another_users_ticket(): void
    {
        // Create a ticket for admin
        $ticket = Ticket::create([
            'title' => 'Admin Ticket',
            'description' => 'Admin ticket description',
            'status' => 'open',
            'priority' => 'high',
            'category' => 'billing',
            'user_id' => $this->admin->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/tickets/' . $ticket->id);

        // User shouldn't be able to access another user's ticket
        $response->assertStatus(403);
    }

    /**
     * Test admin can view any ticket.
     */
    public function test_admin_can_view_any_ticket(): void
    {
        // Create a ticket for regular user
        $ticket = Ticket::create([
            'title' => 'User Ticket',
            'description' => 'User ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'technical',
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/tickets/' . $ticket->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'title' => 'User Ticket',
                    'user_id' => $this->user->id
                ]
            ]);
    }

    /**
     * Test updating a ticket.
     */
    public function test_admin_can_update_ticket_status(): void
    {
        // Create a ticket
        $ticket = Ticket::create([
            'title' => 'Test Ticket',
            'description' => 'Test ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'general',
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'status' => 'in_progress',
            'priority' => 'high'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/tickets/' . $ticket->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ticket updated successfully'
            ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
            'priority' => 'high'
        ]);
    }

    /**
     * Test user can update their own ticket.
     */
    public function test_user_can_update_own_ticket(): void
    {
        // Create a ticket for the user
        $ticket = Ticket::create([
            'title' => 'User Ticket',
            'description' => 'Original description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'technical',
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'description' => 'Updated description'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/api/tickets/' . $ticket->id, $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'description' => 'Updated description'
        ]);
    }

    /**
     * Test user cannot update another user's ticket.
     */
    public function test_user_cannot_update_another_users_ticket(): void
    {
        // Create a ticket for admin
        $ticket = Ticket::create([
            'title' => 'Admin Ticket',
            'description' => 'Admin ticket description',
            'status' => 'open',
            'priority' => 'high',
            'category' => 'billing',
            'user_id' => $this->admin->id
        ]);

        $updateData = [
            'description' => 'Trying to update'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/api/tickets/' . $ticket->id, $updateData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
            'description' => 'Trying to update'
        ]);
    }

    /**
     * Test marking tickets as read.
     */
    public function test_admin_can_mark_ticket_as_read(): void
    {
        // Create a ticket
        $ticket = Ticket::create([
            'title' => 'Test Ticket',
            'description' => 'Test ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'general',
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/tickets/' . $ticket->id . '/mark-read');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ticket marked as read'
            ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'is_read' => true,
            'read_by' => $this->admin->id
        ]);
    }

    /**
     * Test user cannot mark tickets as read.
     */
    public function test_user_cannot_mark_ticket_as_read(): void
    {
        // Create a ticket
        $ticket = Ticket::create([
            'title' => 'Test Ticket',
            'description' => 'Test ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'general',
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/api/tickets/' . $ticket->id . '/mark-read');

        $response->assertStatus(403);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'is_read' => false,
            'read_by' => null
        ]);
    }

    /**
     * Test getting unread tickets count.
     */
    public function test_get_unread_tickets_count(): void
    {
        // Create three tickets with different read statuses
        Ticket::create([
            'title' => 'Unread Ticket 1',
            'description' => 'Unread ticket description',
            'status' => 'open',
            'priority' => 'medium',
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        Ticket::create([
            'title' => 'Unread Ticket 2',
            'description' => 'Unread ticket description',
            'status' => 'open',
            'priority' => 'high',
            'user_id' => $this->admin->id,
            'is_read' => false
        ]);

        Ticket::create([
            'title' => 'Read Ticket',
            'description' => 'Read ticket description',
            'status' => 'in_progress',
            'priority' => 'low',
            'user_id' => $this->user->id,
            'is_read' => true,
            'read_at' => now(),
            'read_by' => $this->admin->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/tickets/unread/count');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'count' => 2
                ]
            ]);
    }
}
