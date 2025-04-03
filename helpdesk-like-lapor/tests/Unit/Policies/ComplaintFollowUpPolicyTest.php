<?php

namespace Tests\Unit\Policies;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\ComplaintFollowUp;
use App\Models\User;
use App\Policies\ComplaintFollowUpPolicy;
use Tests\TestCase;

class ComplaintFollowUpPolicyTest extends TestCase
{
    protected ComplaintFollowUpPolicy $policy;
    protected User $admin;
    protected User $manager;
    protected User $otherManager;
    protected User $reporter;
    protected Agency $agency;
    protected Complaint $assignedComplaint; // Complaint assigned to manager's agency
    protected Complaint $otherComplaint; // Complaint assigned elsewhere or unassigned
    protected ComplaintFollowUp $followUp; // A sample follow-up

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ComplaintFollowUpPolicy();
        $this->admin = User::factory()->make(['role' => 'Admin']);
        $this->agency = Agency::factory()->make();
        $this->manager = User::factory()->make(['role' => 'Agency Manager', 'agency_id' => $this->agency->id]);
        $this->otherManager = User::factory()->make(['role' => 'Agency Manager', 'agency_id' => Agency::factory()->make()->id]);
        $this->reporter = User::factory()->make(['role' => 'Reporter']);

        $this->assignedComplaint = Complaint::factory()->make(['agency_id' => $this->agency->id, 'status' => 'In Progress']);
        $this->otherComplaint = Complaint::factory()->make(['agency_id' => $this->otherManager->agency_id]);
        $this->followUp = ComplaintFollowUp::factory()->make(['complaint_id' => $this->assignedComplaint->id]); // Sample doesn't need DB
    }

    // create (depends on Complaint)
    public function test_admin_can_create_follow_up_for_any_complaint(): void
    {
        $this->assertTrue($this->policy->create($this->admin, $this->assignedComplaint));
        $this->assertTrue($this->policy->create($this->admin, $this->otherComplaint));
    }

    public function test_manager_can_create_follow_up_for_assigned_complaint(): void
    {
        $this->assertTrue($this->policy->create($this->manager, $this->assignedComplaint));
    }

    public function test_manager_cannot_create_follow_up_for_other_complaint(): void
    {
        $this->assertFalse($this->policy->create($this->manager, $this->otherComplaint));
    }

    public function test_reporter_cannot_create_follow_up(): void
    {
        $this->assertFalse($this->policy->create($this->reporter, $this->assignedComplaint));
    }

    public function test_cannot_create_follow_up_for_archived_complaint(): void
    {
        $this->assignedComplaint->status = 'Archived';
        $this->assertFalse($this->policy->create($this->manager, $this->assignedComplaint));
         // Admin might still be allowed based on policy logic, currently true
         $this->assertTrue($this->policy->create($this->admin, $this->assignedComplaint));
    }


    // update (should always be false)
    public function test_no_one_can_update_follow_up(): void
    {
        $this->assertFalse($this->policy->update($this->admin, $this->followUp));
        $this->assertFalse($this->policy->update($this->manager, $this->followUp));
        $this->assertFalse($this->policy->update($this->reporter, $this->followUp));
    }

    // delete (Admin only)
    public function test_admin_can_delete_follow_up(): void { $this->assertTrue($this->policy->delete($this->admin, $this->followUp)); }
    public function test_manager_cannot_delete_follow_up(): void { $this->assertFalse($this->policy->delete($this->manager, $this->followUp)); }
    public function test_reporter_cannot_delete_follow_up(): void { $this->assertFalse($this->policy->delete($this->reporter, $this->followUp)); }
}