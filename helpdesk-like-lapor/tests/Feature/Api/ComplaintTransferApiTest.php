<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\ComplaintTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplaintTransferApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected Agency $agency1;
    protected Agency $agency2;
    protected Complaint $complaint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->manager = User::factory()->create(['role' => 'Agency Manager']); // Not used for transfer initiation in this setup
        $this->agency1 = Agency::factory()->create(['name' => 'Agency One']);
        $this->agency2 = Agency::factory()->create(['name' => 'Agency Two']);

        // Complaint initially assigned to agency1
        $this->complaint = Complaint::factory()->create([
            'agency_id' => $this->agency1->id,
            'status' => 'Pending'
        ]);
    }

    public function test_admin_can_transfer_complaint(): void
    {
        Sanctum::actingAs($this->admin);
        $data = [
            'to_agency_id' => $this->agency2->id,
            'reason' => 'Incorrect initial assignment.',
        ];

        $response = $this->postJson(route('complaints.transfer', $this->complaint), $data);
      


        $response->assertStatus(200)
                // Check response contains updated complaint data
                 ->assertJsonPath('agency.id', $this->agency2->id);

        // Check Complaint updated in DB
        $this->assertDatabaseHas('complaints', [
            'id' => $this->complaint->id,
            'agency_id' => $this->agency2->id,
        ]);

        // Check Transfer record created
        $this->assertDatabaseHas('complaint_transfers', [
            'complaint_id' => $this->complaint->id,
            'from_agency_id' => $this->agency1->id,
            'to_agency_id' => $this->agency2->id,
            'user_id' => $this->admin->id,
            'reason' => 'Incorrect initial assignment.',
        ]);

        // Check Log created
        $this->assertDatabaseHas('complaint_logs', [
            'complaint_id' => $this->complaint->id,
            'action' => 'Transferred',
            'user_id' => $this->admin->id,
            // Check details contains agency names and reason
        ]);
         $log = \App\Models\ComplaintLog::where('complaint_id', $this->complaint->id)->where('action', 'Transferred')->latest('timestamp')->first();
         $this->assertStringContainsString("Transferred from Agency: Agency One to Agency: Agency Two.", $log->details);
         $this->assertStringContainsString("Reason: Incorrect initial assignment.", $log->details);
    }

     public function test_admin_can_transfer_complaint_from_unassigned(): void
     {
         $unassignedComplaint = Complaint::factory()->create(['agency_id' => null, 'status' => 'Unprocessed']);
         Sanctum::actingAs($this->admin);
         $data = ['to_agency_id' => $this->agency1->id]; // Assign for the first time

         $response = $this->postJson(route('complaints.transfer', $unassignedComplaint), $data);
         $response->assertStatus(200)->assertJsonPath('agency.id', $this->agency1->id);
         $this->assertDatabaseHas('complaints', ['id' => $unassignedComplaint->id, 'agency_id' => $this->agency1->id]);
         $this->assertDatabaseHas('complaint_transfers', ['complaint_id' => $unassignedComplaint->id, 'from_agency_id' => null, 'to_agency_id' => $this->agency1->id]);
         // Check log action should be 'Assigned' in this case if service handles it, or maybe still 'Transferred'
         $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $unassignedComplaint->id, 'action' => 'Transferred', 'details' => 'Transferred from Agency: Unassigned to Agency: Agency One.']);
         // Or assert action is 'Assigned' if service logic differentiates
     }


    public function test_non_admin_cannot_initiate_transfer(): void
    {
        $data = ['to_agency_id' => $this->agency2->id];
        // Manager cannot initiate (based on current policy)
        Sanctum::actingAs($this->manager);
        $this->postJson(route('complaints.transfer', $this->complaint), $data)->assertStatus(403);

        // Reporter cannot initiate
        $reporter = User::factory()->create(['role'=>'Reporter']);
        Sanctum::actingAs($reporter);
         $this->postJson(route('complaints.transfer', $this->complaint), $data)->assertStatus(403);
    }

     public function test_cannot_transfer_resolved_or_archived_complaint(): void
     {
         Sanctum::actingAs($this->admin);
         $data = ['to_agency_id' => $this->agency2->id];

         $this->complaint->update(['status' => 'Resolved']);
         $this->postJson(route('complaints.transfer', $this->complaint), $data)->assertStatus(403); // Forbidden by policy

         $this->complaint->update(['status' => 'Archived']);
         $this->postJson(route('complaints.transfer', $this->complaint), $data)->assertStatus(403); // Forbidden by policy
     }

    public function test_transfer_validation(): void
    {
        Sanctum::actingAs($this->admin);
        // Missing to_agency_id
        $res1 = $this->postJson(route('complaints.transfer', $this->complaint), ['reason' => 'Test']);
        $res1->assertStatus(422)->assertJsonValidationErrors(['to_agency_id']);
        // Invalid to_agency_id
        $res2 = $this->postJson(route('complaints.transfer', $this->complaint), ['to_agency_id' => 'invalid-uuid']);
        $res2->assertStatus(422)->assertJsonValidationErrors(['to_agency_id']);
        // Transferring to same agency
        $res3 = $this->postJson(route('complaints.transfer', $this->complaint), ['to_agency_id' => $this->agency1->id]);
        $res3->assertStatus(422)->assertJsonValidationErrors(['to_agency_id']);
    }

     public function test_transfer_returns_404_if_complaint_not_found(): void
     {
         Sanctum::actingAs($this->admin);
         $response = $this->postJson(route('complaints.transfer', 'non-existent-uuid'), ['to_agency_id' => $this->agency2->id]);
         $response->assertStatus(404);
     }
}