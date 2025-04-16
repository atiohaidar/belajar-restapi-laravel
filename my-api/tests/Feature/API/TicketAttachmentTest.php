<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Role;
use App\Models\User;
use Modules\Ticketing\app\Models\Ticket;
use Modules\Ticketing\app\Models\TicketAttachment;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
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

        // Create fake storage disk
        Storage::fake('public');

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

        // Create a ticket for testing attachments
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
     * Test uploading an attachment to a ticket.
     */
    public function test_user_can_upload_attachment(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/tickets/' . $this->ticket->id . '/attachments', [
            'file' => $file,
            'is_public' => true
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'ticket_id',
                    'user_id',
                    'filename',
                    'original_filename',
                    'file_path',
                    'file_type',
                    'file_size',
                    'is_public',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'original_filename' => 'document.pdf',
            'is_public' => 1
        ]);

        // Check that the file was stored
        $attachment = TicketAttachment::first();
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    /**
     * Test listing attachments for a ticket.
     */
    public function test_list_attachments_for_ticket(): void
    {
        // Create a file attachment manually
        $filename = 'test_file.txt';
        $path = 'ticket-attachments/' . $this->ticket->id . '/' . $filename;
        
        Storage::disk('public')->put($path, 'Test file content');
        
        TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'filename' => $filename,
            'original_filename' => 'original.txt',
            'file_path' => $path,
            'file_type' => 'text/plain',
            'file_size' => 17,
            'is_public' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/tickets/' . $this->ticket->id . '/attachments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'ticket_id',
                        'user_id',
                        'filename',
                        'original_filename',
                        'file_path',
                        'file_type',
                        'file_size',
                        'is_public',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test viewing a specific attachment.
     */
    public function test_view_specific_attachment(): void
    {
        // Create a file attachment manually
        $filename = 'test_file.txt';
        $path = 'ticket-attachments/' . $this->ticket->id . '/' . $filename;
        
        Storage::disk('public')->put($path, 'Test file content');
        
        $attachment = TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'filename' => $filename,
            'original_filename' => 'original.txt',
            'file_path' => $path,
            'file_type' => 'text/plain',
            'file_size' => 17,
            'is_public' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/tickets/' . $this->ticket->id . '/attachments/' . $attachment->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'ticket_id',
                    'user_id',
                    'filename',
                    'original_filename',
                    'file_path',
                    'file_type',
                    'file_size',
                    'is_public',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test downloading an attachment.
     * Note: We can only test the response structure since actual file download can't be fully tested in feature tests
     */
    public function test_download_attachment(): void
    {
        // Create a file attachment manually
        $filename = 'test_file.txt';
        $path = 'ticket-attachments/' . $this->ticket->id . '/' . $filename;
        
        Storage::disk('public')->put($path, 'Test file content');
        
        $attachment = TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'filename' => $filename,
            'original_filename' => 'original.txt',
            'file_path' => $path,
            'file_type' => 'text/plain',
            'file_size' => 17,
            'is_public' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->get('/api/tickets/' . $this->ticket->id . '/attachments/' . $attachment->id . '/download');
            print_r($attachment->id);
        $response->assertStatus(200);
        $this->assertEquals('Test file content', $response->getContent());
    }

    /**
     * Test deleting an attachment.
     */
    public function test_user_can_delete_own_attachment(): void
    {
        // Create a file attachment manually
        $filename = 'test_file.txt';
        $path = 'ticket-attachments/' . $this->ticket->id . '/' . $filename;
        
        Storage::disk('public')->put($path, 'Test file content');
        
        $attachment = TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'filename' => $filename,
            'original_filename' => 'original.txt',
            'file_path' => $path,
            'file_type' => 'text/plain',
            'file_size' => 17,
            'is_public' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/api/tickets/' . $this->ticket->id . '/attachments/' . $attachment->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Attachment deleted successfully'
            ]);

        // Check the database record is gone
        $this->assertDatabaseMissing('ticket_attachments', [
            'id' => $attachment->id
        ]);

        // Check the file is deleted from storage
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * Test user cannot delete another user's attachment.
     */
    public function test_user_cannot_delete_another_users_attachment(): void
    {
        // Create a file attachment belonging to admin
        $filename = 'admin_file.txt';
        $path = 'ticket-attachments/' . $this->ticket->id . '/' . $filename;
        
        Storage::disk('public')->put($path, 'Admin file content');
        
        $attachment = TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->admin->id,  // Admin's attachment
            'filename' => $filename,
            'original_filename' => 'original.txt',
            'file_path' => $path,
            'file_type' => 'text/plain',
            'file_size' => 17,
            'is_public' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/api/tickets/' . $this->ticket->id . '/attachments/' . $attachment->id);

        $response->assertStatus(403);

        // Check the database record still exists
        $this->assertDatabaseHas('ticket_attachments', [
            'id' => $attachment->id
        ]);

        // Check the file still exists
        Storage::disk('public')->assertExists($path);
    }

    /**
     * Test file size limits.
     */
    public function test_file_size_limits(): void
    {
        // Create a file that's too large (11MB)
        $largeFile = UploadedFile::fake()->create('large_document.pdf', 11000);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/tickets/' . $this->ticket->id . '/attachments', [
            'file' => $largeFile
        ]);

        // Should fail validation
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * Test admin can see attachments in ticket details.
     */
    public function test_admin_can_see_attachments_in_ticket_details(): void
    {
        // Create a file attachment 
        $filename = 'test_file.txt';
        $path = 'ticket-attachments/' . $this->ticket->id . '/' . $filename;
        
        Storage::disk('public')->put($path, 'Test file content');
        
        TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'filename' => $filename,
            'original_filename' => 'original.txt',
            'file_path' => $path,
            'file_type' => 'text/plain',
            'file_size' => 17,
            'is_public' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/tickets/' . $this->ticket->id);

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
                    'user',
                    'attachments' => [
                        '*' => [
                            'id',
                            'filename',
                            'original_filename',
                            'file_type',
                            'file_size'
                        ]
                    ]
                ]
            ]);
    }
}
