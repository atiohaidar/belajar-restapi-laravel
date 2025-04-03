<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\Comment;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\ComplaintAttachment; // Import attachment model
use App\Models\ComplaintLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile; // Import UploadedFile
use Illuminate\Support\Facades\Storage; // Import Storage facade
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplaintApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected Agency $managerAgency;
    protected User $otherManager;
    protected User $guest;
    protected User $reporter;
    protected User $otherReporter;
    protected ComplaintCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guest = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->managerAgency = Agency::factory()->create();
        $this->manager = User::factory()->create(['role' => 'Agency Manager', 'agency_id' => $this->managerAgency->id]);
        $this->otherManager = User::factory()->create(['role' => 'Agency Manager', 'agency_id' => Agency::factory()->create()->id]);
        $this->reporter = User::factory()->create(['role' => 'Reporter']);
        $this->otherReporter = User::factory()->create(['role' => 'Reporter']);
        $this->category = ComplaintCategory::factory()->create();

        // Setup fake storage disk for testing uploads
        Storage::fake('public');
    }

    // --- INDEX ---
    public function test_reporter_can_list_only_own_complaints(): void
    {
        Complaint::factory()->count(3)->create(['user_id' => $this->reporter->id]);
        Complaint::factory()->count(2)->create(['user_id' => $this->otherReporter->id]);
        Sanctum::actingAs($this->reporter);

        $response = $this->getJson(route('complaints.index'));

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data')
                 ->assertJsonPath('data.0.reporter_id', $this->reporter->id)
                 ->assertJsonPath('data.1.reporter_id', $this->reporter->id)
                 ->assertJsonPath('data.2.reporter_id', $this->reporter->id);
    }

    public function test_manager_can_list_only_complaints_in_their_agency(): void
    {
        Complaint::factory()->count(2)->create(['agency_id' => $this->managerAgency->id]); // In manager's agency
        Complaint::factory()->count(1)->create(['user_id' => $this->reporter->id]); // Reporter's, unassigned
        Complaint::factory()->count(3)->create(['agency_id' => Agency::factory()->create()->id]); // Other agency

        Sanctum::actingAs($this->manager);
        $response = $this->getJson(route('complaints.index'));

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJsonPath('data.0.assigned_agency_id', $this->managerAgency->id)
                 ->assertJsonPath('data.1.assigned_agency_id', $this->managerAgency->id);
    }

    public function test_admin_can_list_all_complaints_with_filters(): void
    {
        $comp1 = Complaint::factory()->create(['agency_id' => $this->managerAgency->id, 'status' => 'Pending', 'priority' => 'High']);
        $comp2 = Complaint::factory()->create(['agency_id' => null, 'status' => 'Unprocessed', 'priority' => 'Medium', 'category_id' => $this->category->id]);
        $comp3 = Complaint::factory()->create(['agency_id' => Agency::factory()->create()->id, 'status' => 'Pending', 'priority' => 'Low']);

        Sanctum::actingAs($this->admin);

        // All
        $resAll = $this->getJson(route('complaints.index'));
        $resAll->assertStatus(200)->assertJsonCount(3, 'data');

        // Filter Status
        $resStat = $this->getJson(route('complaints.index', ['status' => 'Pending']));
        $resStat->assertStatus(200)->assertJsonCount(2, 'data');

        // Filter Priority
        $resPri = $this->getJson(route('complaints.index', ['priority' => 'Medium']));
        $resPri->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $comp2->id);

        // Filter Agency (Admin only)
        $resAgen = $this->getJson(route('complaints.index', ['agency_id' => $this->managerAgency->id]));
        $resAgen->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $comp1->id);

         // Filter Category
         $resCat = $this->getJson(route('complaints.index', ['category_id' => $this->category->id]));
         $resCat->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $comp2->id);
    }

    // --- STORE ---
    public function test_reporter_can_create_complaint(): void
    {
        Sanctum::actingAs($this->reporter);
        $data = [
            'title' => 'Test Complaint Title',
            'description' => 'Detailed description of the issue.',
            'category_id' => $this->category->id,
            'priority' => 'High',
        ];
        $response = $this->postJson(route('complaints.store'), $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['title' => 'Test Complaint Title', 'priority' => 'High'])
                 ->assertJsonPath('data.reporter_id', $this->reporter->id)
                 ->assertJsonPath('data.status', 'Unprocessed')
                 ->assertJsonPath('data.assigned_agency_id', null); // Initially unassigned

        $this->assertDatabaseHas('complaints', ['title' => 'Test Complaint Title', 'user_id' => $this->reporter->id]);
         // Check log exists
         $complaint = Complaint::where('title', 'Test Complaint Title')->first();
         $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $complaint->id, 'action' => 'Created']);
    }

     public function test_reporter_can_create_complaint_with_attachments(): void
     {
        $this->markTestSkipped('Ini di pending dulu karena belum ada fitur upload file, serta masih bermasalah di extension gd di laravel');

         Sanctum::actingAs($this->reporter);
         $file1 = UploadedFile::fake()->image('photo1.jpg');
         $file2 = UploadedFile::fake()->create('document.pdf', 100); // Create fake PDF

         $data = [
             'title' => 'Complaint With Files',
             'description' => 'See attached files.',
             'category_id' => $this->category->id,
             'attachments' => [$file1, $file2], // Send array of files
         ];

         $response = $this->postJson(route('complaints.store'), $data);

         $response->assertStatus(201);
         $complaint = Complaint::where('title', 'Complaint With Files')->first();
         $this->assertCount(2, $complaint->attachments);

         $attachmentRecord1 = $complaint->attachments[0];
         $attachmentRecord2 = $complaint->attachments[1];

         // Assert file exists in fake storage
         Storage::disk('public')->assertExists($attachmentRecord1->file_path);
         Storage::disk('public')->assertExists($attachmentRecord2->file_path);
         $this->assertEquals('photo1.jpg', $attachmentRecord1->file_name); // Check original name saved correctly
         $this->assertEquals('document.pdf', $attachmentRecord2->file_name);

         // Check resource includes attachment URLs
         $response->assertJsonCount(2, 'data.attachments');
         $response->assertJsonStructure(['data' => ['attachments' => [['id', 'file_name', 'url', 'uploaded_at']]]]);
     }


    public function test_non_reporter_cannot_create_complaint(): void
    {
         $data = ['title' => 'T', 'description' => 'D', 'category_id' => $this->category->id];
         foreach ([$this->admin, $this->manager] as $user) {
             Sanctum::actingAs($user);
             $response = $this->postJson(route('complaints.store'), $data);
             $response->assertStatus(403); // Forbidden by policy/request authorize
         }
    }

    public function test_create_complaint_validation(): void
    {
        Sanctum::actingAs($this->reporter);
        // Missing fields
        $res1 = $this->postJson(route('complaints.store'), ['title' => 'T']);
        $res1->assertStatus(422)->assertJsonValidationErrors(['description', 'category_id']);
        // Invalid category
        $res2 = $this->postJson(route('complaints.store'), ['title' => 'T', 'description' => 'D', 'category_id' => 'invalid-uuid']);
        $res2->assertStatus(422)->assertJsonValidationErrors(['category_id']);
         // Invalid attachment type/size
         $fileTooBig = UploadedFile::fake()->create('bigfile.txt', 6000); // > 5MB
         $fileWrongType = UploadedFile::fake()->create('script.js');
         $res3 = $this->postJson(route('complaints.store'), [
             'title'=>'T', 'description'=>'D', 'category_id'=>$this->category->id, 'attachments'=>[$fileTooBig]
         ]);
         $res3->assertStatus(422)->assertJsonValidationErrors(['attachments.0']);
         $res4 = $this->postJson(route('complaints.store'), [
             'title'=>'T', 'description'=>'D', 'category_id'=>$this->category->id, 'attachments'=>[$fileWrongType]
         ]);
         $res4->assertStatus(422)->assertJsonValidationErrors(['attachments.0']);
    }

    // --- SHOW ---
    public function test_authorized_users_can_view_complaint_details(): void
    {
        $complaint = Complaint::factory()
                        ->for($this->reporter) // Link reporter using for()
                        ->for($this->managerAgency, 'agency') // Link agency using for()
                        ->for($this->category)
                        ->has(ComplaintAttachment::factory()->count(1), 'attachments') // Create attachment
                        ->has(Comment::factory()->for($this->manager), 'comments') // Create comment by manager
                        ->has(ComplaintLog::factory()->for($this->admin), 'logs') // Create log by admin
                        ->create();

        // Admin can view
        Sanctum::actingAs($this->admin);
        $resAdmin = $this->getJson(route('complaints.show', $complaint));
        $resAdmin->assertStatus(200)
                 ->assertJsonPath('data.id', $complaint->id)
                 ->assertJsonCount(1, 'data.attachments') // Check relationship counts
                 ->assertJsonCount(1, 'data.comments')
                 ->assertJsonCount(1, 'data.logs')
                 ->assertJsonStructure(['data' => ['user', 'agency', 'category', 'attachments', 'comments', 'logs']]); // Check structure

        // Reporter (owner) can view
        Sanctum::actingAs($this->reporter);
        $resReporter = $this->getJson(route('complaints.show', $complaint));
        $resReporter->assertStatus(200)->assertJsonPath('data.id', $complaint->id);

        // Manager (assigned agency) can view
        Sanctum::actingAs($this->manager);
        $resManager = $this->getJson(route('complaints.show', $complaint));
        $resManager->assertStatus(200)->assertJsonPath('data.id', $complaint->id);
    }

    public function test_unauthorized_users_cannot_view_complaint_details(): void
    {
         $complaint = Complaint::factory()->create([
             'user_id' => $this->reporter->id,
             'agency_id' => $this->managerAgency->id,
         ]);

         // Unauthenticated cannot view
         $this->getJson(route('complaints.show', $complaint))->assertStatus(401);
         // Other reporter cannot view
         Sanctum::actingAs($this->otherReporter);
         $this->getJson(route('complaints.show', $complaint))->assertStatus(403);

         // Other manager cannot view
         Sanctum::actingAs($this->otherManager);
         $this->getJson(route('complaints.show', $complaint))->assertStatus(403);
         
    }

    // --- UPDATE ---
    public function test_admin_can_update_complaint_status_priority_agency(): void
    {
        $complaint = Complaint::factory()->create(['status' => 'Unprocessed', 'priority' => 'Low', 'agency_id' => null]);
        $newAgency = Agency::factory()->create();
        Sanctum::actingAs($this->admin);
        $data = [
            'status' => 'Pending',
            'priority' => 'High',
            'agency_id' => $newAgency->id,
        ];
        $response = $this->putJson(route('complaints.update', $complaint), $data);
        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'Pending', 'priority' => 'High'])
                 ->assertJsonPath('data.assigned_agency_id', $newAgency->id);

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'status' => 'Pending', 'agency_id' => $newAgency->id]);
         // Check logs
         $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $complaint->id, 'action' => 'Status Update', 'details' => 'Status changed from Unprocessed to Pending.']);
         $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $complaint->id, 'action' => 'Priority Change']);
         $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $complaint->id, 'action' => 'Assigned']);
    }

     public function test_manager_can_update_status_priority_of_assigned_complaint(): void
     {
         $complaint = Complaint::factory()->create([
             'status' => 'Pending',
             'priority' => 'Medium',
             'agency_id' => $this->managerAgency->id
         ]);
         Sanctum::actingAs($this->manager);
         $data = [
             'status' => 'In Progress',
             'priority' => 'High',
         ];
          $response = $this->putJson(route('complaints.update', $complaint), $data);
          $response->assertStatus(200)
                   ->assertJsonFragment(['status' => 'In Progress', 'priority' => 'High']);
          $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'status' => 'In Progress', 'priority' => 'High']);
          $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $complaint->id, 'action' => 'Status Update']);
          $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $complaint->id, 'action' => 'Priority Change']);
     }

    public function test_manager_cannot_update_agency_via_general_update(): void
    {
         $complaint = Complaint::factory()->create(['agency_id' => $this->managerAgency->id]);
         $otherAgency = Agency::factory()->create();
         Sanctum::actingAs($this->manager);
         $data = ['agency_id' => $otherAgency->id]; // Attempt to transfer
         // UpdateComplaintRequest rules (as designed) should filter this out for manager
         $response = $this->putJson(route('complaints.update', $complaint), $data);
         // Depending on request logic: might be 200 OK but agency_id not changed, or 400/403 if validation strict
         $response->assertStatus(400); // Assume it passes but doesn't update restricted field
         $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'agency_id' => $this->managerAgency->id]); // Still original agency
    }

     public function test_reporter_cannot_update_complaint(): void
     {
         $complaint = Complaint::factory()->create(['user_id' => $this->reporter->id]);
         Sanctum::actingAs($this->reporter);
         $data = ['status' => 'Pending']; // Attempt to update status
         $response = $this->putJson(route('complaints.update', $complaint), $data);
         $response->assertStatus(403); // Forbidden by policy/request authorize
     }

     public function test_update_complaint_validation(): void
     {
         $complaint = Complaint::factory()->create(['agency_id' => $this->managerAgency->id]);
         Sanctum::actingAs($this->admin); // Use admin for full update capability
         // Invalid status
         $res1 = $this->putJson(route('complaints.update', $complaint), ['status' => 'InvalidStatus']);
         $res1->assertStatus(422)->assertJsonValidationErrors(['status']);
         // Invalid agency
          $res2 = $this->putJson(route('complaints.update', $complaint), ['agency_id' => 'invalid-uuid']);
          $res2->assertStatus(422)->assertJsonValidationErrors(['agency_id']);
     }


    // --- DELETE ---
    public function test_admin_can_delete_complaint(): void
    {
        // Create complaint with attachment to test file deletion too
        $complaint = Complaint::factory()->create();
   
        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson(route('complaints.destroy', $complaint));
        $response->assertStatus(204);

        $this->assertDatabaseMissing('complaints', ['id' => $complaint->id]);
        $this->assertDatabaseMissing('complaint_attachments', ['complaint_id' => $complaint->id]); // Check cascade
    }
    public function test_admin_can_delete_complaint_with_a_attachment_path(): void
    {
        $this->markTestSkipped('Ini di pending dulu karena belum ada fitur upload file');
        // Create complaint with attachment to test file deletion too
        $complaint = Complaint::factory()->has(ComplaintAttachment::factory(), 'attachments')->create();
        $attachmentPath = $complaint->attachments()->first()->file_path;
        print_r($attachmentPath);
        Storage::disk('public')->assertExists($attachmentPath); // Ensure file exists initially

        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson(route('complaints.destroy', $complaint));
        $response->assertStatus(204);

        $this->assertDatabaseMissing('complaints', ['id' => $complaint->id]);
        $this->assertDatabaseMissing('complaint_attachments', ['complaint_id' => $complaint->id]); // Check cascade
        Storage::disk('public')->assertMissing($attachmentPath); // Check file deleted from storage
    }

    public function test_non_admin_cannot_delete_complaint(): void
    {
         $complaint = Complaint::factory()->create();
         foreach ([$this->manager, $this->reporter] as $user) {
             Sanctum::actingAs($user);
             $response = $this->deleteJson(route('complaints.destroy', $complaint));
             $response->assertStatus(403);
         }
         $this->assertDatabaseHas('complaints', ['id' => $complaint->id]); // Ensure it wasn't deleted
    }
}