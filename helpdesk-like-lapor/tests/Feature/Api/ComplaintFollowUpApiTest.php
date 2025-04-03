<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\ComplaintFollowUp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplaintFollowUpApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected Agency $managerAgency;
    protected User $otherManager;
    protected User $reporter;
    protected Complaint $complaint; // Complaint assigned to manager's agency

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->managerAgency = Agency::factory()->create();
        $this->manager = User::factory()->create(['role' => 'Agency Manager', 'agency_id' => $this->managerAgency->id]);
        $this->otherManager = User::factory()->create(['role' => 'Agency Manager', 'agency_id' => Agency::factory()->create()->id]);
        $this->reporter = User::factory()->create(['role' => 'Reporter']);

        $this->complaint = Complaint::factory()->create([
            'user_id' => $this->reporter->id,
            'agency_id' => $this->managerAgency->id,
            'status' => 'In Progress'
        ]);
    }

    // --- STORE ---
    public function test_manager_can_add_follow_up_to_assigned_complaint(): void
    {
        Sanctum::actingAs($this->manager);
        $data = ['description' => 'Contacted relevant department. Waiting for response.'];

        $response = $this->postJson(route('complaints.follow-ups.store', $this->complaint), $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['description' => $data['description']])
                 ->assertJsonPath('data.user.id', $this->manager->id)
                 ->assertJsonPath('data.agency_id', $this->managerAgency->id); // Check agency context saved

        $this->assertDatabaseHas('complaint_follow_ups', [
            'complaint_id' => $this->complaint->id,
            'user_id' => $this->manager->id,
            'description' => $data['description'],
            'agency_id' => $this->managerAgency->id,
        ]);
        // Check log
        $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $this->complaint->id, 'action' => 'Follow-up Added']);
    }

    public function test_admin_can_add_follow_up_to_any_complaint(): void
    {
        Sanctum::actingAs($this->admin);
        $data = ['description' => 'Admin reviewed the case. Assigned priority raised.'];

        $response = $this->postJson(route('complaints.follow-ups.store', $this->complaint), $data);

        $response->assertStatus(201)
                 ->assertJsonPath('data.user.id', $this->admin->id)
                 ->assertJsonPath('data.agency_id', null); // Admin might not have agency context here

        $this->assertDatabaseHas('complaint_follow_ups', ['description' => $data['description'], 'user_id' => $this->admin->id]);
        $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $this->complaint->id, 'action' => 'Follow-up Added']);
    }

    public function test_unauthorized_users_cannot_add_follow_up(): void
    {
        $otherComplaint = Complaint::factory()->create(['agency_id' => $this->otherManager->agency_id]);
        $data = ['description' => 'Unauthorized attempt.'];

        // Reporter cannot add
        Sanctum::actingAs($this->reporter);
        $this->postJson(route('complaints.follow-ups.store', $this->complaint), $data)->assertStatus(403);

        // Other manager cannot add
        Sanctum::actingAs($this->otherManager);
        $this->postJson(route('complaints.follow-ups.store', $this->complaint), $data)->assertStatus(403);

        // Manager cannot add to other's complaint
        Sanctum::actingAs($this->manager);
        $this->postJson(route('complaints.follow-ups.store', $otherComplaint), $data)->assertStatus(403);
    }

    public function test_store_follow_up_validation(): void
    {
        Sanctum::actingAs($this->manager);
        // Missing description
        $res1 = $this->postJson(route('complaints.follow-ups.store', $this->complaint), []);
        $res1->assertStatus(422)->assertJsonValidationErrors(['description']);
        // Description too short
        $res2 = $this->postJson(route('complaints.follow-ups.store', $this->complaint), ['description' => 'Too short']);
        $res2->assertStatus(422)->assertJsonValidationErrors(['description']);
    }

    // --- DELETE ---
    public function test_admin_can_delete_follow_up(): void
    {
        $followUp = ComplaintFollowUp::factory()->create(['complaint_id' => $this->complaint->id]);
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson(route('complaint-follow-ups.destroy', $followUp));
        $response->assertStatus(204);
        $this->assertDatabaseMissing('complaint_follow_ups', ['id' => $followUp->id]);
    }

    public function test_non_admin_cannot_delete_follow_up(): void
    {
         $followUp = ComplaintFollowUp::factory()->create([
             'complaint_id' => $this->complaint->id,
             'user_id' => $this->manager->id // Follow up by manager
         ]);

         // Manager (creator) cannot delete
         Sanctum::actingAs($this->manager);
         $this->deleteJson(route('complaint-follow-ups.destroy', $followUp))->assertStatus(403);

         // Reporter cannot delete
         Sanctum::actingAs($this->reporter);
         $this->deleteJson(route('complaint-follow-ups.destroy', $followUp))->assertStatus(403);

         $this->assertDatabaseHas('complaint_follow_ups', ['id' => $followUp->id]); // Ensure not deleted
    }

    public function test_delete_follow_up_returns_404_if_not_found(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson(route('complaint-follow-ups.destroy', 'non-existent-uuid'));
        $response->assertStatus(404);
    }
}