<?php

namespace Tests\Unit\Policies;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\User;
use App\Policies\ComplaintPolicy;
use Tests\TestCase;

class ComplaintPolicyTest extends TestCase
{
    protected ComplaintPolicy $policy;
    protected User $admin;
    protected User $manager;
    protected User $otherManager;
    protected User $reporter;
    protected User $otherReporter;
    protected Agency $agency;
    protected Agency $otherAgency;
    protected Complaint $reporterComplaint; // Complaint made by $reporter
    protected Complaint $assignedComplaint; // Complaint assigned to $agency
    protected Complaint $otherAgencyComplaint; // Complaint assigned to $otherAgency

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ComplaintPolicy();

        $this->admin = User::factory()->make(['role' => 'Admin']);
        $this->agency = Agency::factory()->make(); // Make agencies first
        $this->otherAgency = Agency::factory()->make();

        $this->manager = User::factory()->make(['role' => 'Agency Manager', 'agency_id' => $this->agency->id]);
        $this->otherManager = User::factory()->make(['role' => 'Agency Manager', 'agency_id' => $this->otherAgency->id]);
        $this->reporter = User::factory()->make(['role' => 'Reporter']);
        $this->otherReporter = User::factory()->make(['role' => 'Reporter']);

        // Make complaints (no DB needed for policy tests)
        $this->reporterComplaint = Complaint::factory()->make([
            'user_id' => $this->reporter->id,
            'agency_id' => $this->agency->id // Assume assigned to test manager's agency
        ]);
         $this->assignedComplaint = Complaint::factory()->make([
             'user_id' => $this->otherReporter->id,
             'agency_id' => $this->agency->id // Assigned to manager's agency
         ]);
          $this->otherAgencyComplaint = Complaint::factory()->make([
              'user_id' => $this->otherReporter->id,
              'agency_id' => $this->otherAgency->id // Assigned to other manager's agency
          ]);
    }

    // viewAny - Allow all authenticated
    public function test_authenticated_users_can_potentially_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
        $this->assertTrue($this->policy->viewAny($this->manager));
        $this->assertTrue($this->policy->viewAny($this->reporter));
    }

    // view
    public function test_admin_can_view_any_complaint(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->reporterComplaint));
        $this->assertTrue($this->policy->view($this->admin, $this->assignedComplaint));
        $this->assertTrue($this->policy->view($this->admin, $this->otherAgencyComplaint));
    }

    public function test_reporter_can_view_own_complaint(): void
    {
        $this->assertTrue($this->policy->view($this->reporter, $this->reporterComplaint));
    }

     public function test_reporter_cannot_view_others_complaint(): void
     {
        
         $this->assertFalse($this->policy->view($this->reporter, $this->assignedComplaint));
     }

    public function test_manager_can_view_complaint_in_their_agency(): void
    {
        $this->assertTrue($this->policy->view($this->manager, $this->reporterComplaint));
        $this->assertTrue($this->policy->view($this->manager, $this->assignedComplaint));
    }

    public function test_manager_cannot_view_complaint_in_other_agency(): void
    {
        $this->assertFalse($this->policy->view($this->manager, $this->otherAgencyComplaint));
    }

    // create
    public function test_reporter_can_create_complaint(): void { $this->assertTrue($this->policy->create($this->reporter)); }
    public function test_admin_cannot_create_complaint_via_policy(): void { $this->assertFalse($this->policy->create($this->admin)); } // Explicitly false for this standard endpoint
    public function test_manager_cannot_create_complaint(): void { $this->assertFalse($this->policy->create($this->manager)); }

    // update
    public function test_admin_can_update_any_complaint(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->reporterComplaint));
        $this->assertTrue($this->policy->update($this->admin, $this->assignedComplaint));
    }

    public function test_manager_can_update_complaint_in_their_agency(): void
    {
        $this->assertTrue($this->policy->update($this->manager, $this->reporterComplaint));
        $this->assertTrue($this->policy->update($this->manager, $this->assignedComplaint));
    }

    public function test_manager_cannot_update_complaint_in_other_agency(): void
    {
         $this->assertFalse($this->policy->update($this->manager, $this->otherAgencyComplaint));
    }

    public function test_reporter_cannot_update_complaint(): void
    {
        $this->assertFalse($this->policy->update($this->reporter, $this->reporterComplaint));
    }

    // delete
    public function test_admin_can_delete_complaint(): void { $this->assertTrue($this->policy->delete($this->admin, $this->reporterComplaint)); }
    public function test_manager_cannot_delete_complaint(): void { $this->assertFalse($this->policy->delete($this->manager, $this->assignedComplaint)); }
    public function test_reporter_cannot_delete_complaint(): void { $this->assertFalse($this->policy->delete($this->reporter, $this->reporterComplaint)); }

    // addComment
    public function test_users_can_add_comment_based_on_role_and_ownership(): void
    {
        $this->assertTrue($this->policy->addComment($this->admin, $this->reporterComplaint)); // Admin can comment anywhere
        $this->assertTrue($this->policy->addComment($this->reporter, $this->reporterComplaint)); // Reporter on own complaint
        $this->assertFalse($this->policy->addComment($this->reporter, $this->assignedComplaint)); // Reporter not on other's
        $this->assertTrue($this->policy->addComment($this->manager, $this->assignedComplaint)); // Manager on assigned complaint
        $this->assertFalse($this->policy->addComment($this->manager, $this->otherAgencyComplaint)); // Manager not on other agency's
        $this->assertFalse($this->policy->addComment($this->otherReporter, $this->reporterComplaint)); // Other reporter cannot comment
    }

     public function test_cannot_add_comment_to_archived_complaint(): void
     {
         $archivedComplaint = Complaint::factory()->make([
             'user_id' => $this->reporter->id,
             'agency_id' => $this->agency->id,
             'status' => 'Archived'
         ]);
         $this->assertTrue($this->policy->addComment($this->admin, $archivedComplaint)); // Admin maybe can? Or should be false too? Let's assume admin can.
         $this->assertFalse($this->policy->addComment($this->reporter, $archivedComplaint));
         $this->assertFalse($this->policy->addComment($this->manager, $archivedComplaint));
     }

    // addAttachment (Simplified test logic, assumes non-resolved/archived status)
    public function test_users_can_add_attachment_based_on_role_ownership_status(): void
    {
         $this->reporterComplaint->status = 'Pending'; // Ensure not resolved
         $this->assignedComplaint->status = 'In Progress';
         $this->otherAgencyComplaint->status = 'Pending';
         $resolvedComplaint = Complaint::factory()->make(['user_id' => $this->reporter->id, 'status' => 'Resolved']);

         $this->assertTrue($this->policy->addAttachment($this->admin, $this->reporterComplaint));
         $this->assertTrue($this->policy->addAttachment($this->reporter, $this->reporterComplaint));
         $this->assertFalse($this->policy->addAttachment($this->reporter, $this->assignedComplaint));
         $this->assertTrue($this->policy->addAttachment($this->manager, $this->assignedComplaint));
         $this->assertFalse($this->policy->addAttachment($this->manager, $this->otherAgencyComplaint));
         $this->assertFalse($this->policy->addAttachment($this->reporter, $resolvedComplaint)); // Cannot add to resolved
         $this->assertFalse($this->policy->addAttachment($this->otherReporter, $this->reporterComplaint));
    }
}