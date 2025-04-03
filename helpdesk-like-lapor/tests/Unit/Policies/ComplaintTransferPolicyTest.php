<?php

namespace Tests\Unit\Policies;

use App\Models\Complaint;
use App\Models\ComplaintTransfer;
use App\Models\User;
use App\Policies\ComplaintTransferPolicy;
use Tests\TestCase;

class ComplaintTransferPolicyTest extends TestCase
{
    protected ComplaintTransferPolicy $policy;
    protected User $admin;
    protected User $manager;
    protected User $reporter;
    protected Complaint $complaint; // Generic complaint
    protected ComplaintTransfer $transfer; // Sample transfer record

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ComplaintTransferPolicy();
        $this->admin = User::factory()->make(['role' => 'Admin']);
        $this->manager = User::factory()->make(['role' => 'Agency Manager']);
        $this->reporter = User::factory()->make(['role' => 'Reporter']);
        $this->complaint = Complaint::factory()->make(['status' => 'Pending']); // Make complaint instance
        $this->transfer = ComplaintTransfer::factory()->make(); // Make transfer instance
    }

    // create (depends on Complaint) - Admin only in this example
    public function test_admin_can_initiate_transfer_for_active_complaint(): void
    {
        $this->assertTrue($this->policy->create($this->admin, $this->complaint));
    }

    public function test_admin_cannot_initiate_transfer_for_resolved_complaint(): void
    {
        $this->complaint->status = 'Resolved';
        $this->assertFalse($this->policy->create($this->admin, $this->complaint));
    }
    public function test_admin_cannot_initiate_transfer_for_archived_complaint(): void
    {
        $this->complaint->status = 'Archived';
        $this->assertFalse($this->policy->create($this->admin, $this->complaint));
    }

    public function test_manager_cannot_initiate_transfer(): void
    {
        $this->assertFalse($this->policy->create($this->manager, $this->complaint));
    }

    public function test_reporter_cannot_initiate_transfer(): void
    {
        $this->assertFalse($this->policy->create($this->reporter, $this->complaint));
    }

    // update (always false)
    public function test_no_one_can_update_transfer(): void
    {
        $this->assertFalse($this->policy->update($this->admin, $this->transfer));
        $this->assertFalse($this->policy->update($this->manager, $this->transfer));
        $this->assertFalse($this->policy->update($this->reporter, $this->transfer));
    }

    // delete (Admin only)
    public function test_admin_can_delete_transfer(): void { $this->assertTrue($this->policy->delete($this->admin, $this->transfer)); }
    public function test_manager_cannot_delete_transfer(): void { $this->assertFalse($this->policy->delete($this->manager, $this->transfer)); }
    public function test_reporter_cannot_delete_transfer(): void { $this->assertFalse($this->policy->delete($this->reporter, $this->transfer)); }
}