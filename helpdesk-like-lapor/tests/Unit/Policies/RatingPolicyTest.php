<?php

namespace Tests\Unit\Policies;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\Rating;
use App\Models\User;
use App\Policies\RatingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase; // Use Refresh DB to test exists() check
use Tests\TestCase;

class RatingPolicyTest extends TestCase // Extends base TestCase
{
    use RefreshDatabase; // Necessary for the rateComplaint exists() check

    protected RatingPolicy $policy;
    protected User $admin;
    protected User $manager;
    protected User $reporter;
    protected User $otherReporter;
    protected Agency $agency;
    protected Complaint $resolvedComplaint; // Resolved complaint by reporter for agency
    protected Complaint $pendingComplaint; // Pending complaint by reporter for agency
    protected Complaint $otherResolvedComplaint; // Resolved complaint by other reporter
    protected Rating $rating; // Rating given by reporter for resolvedComplaint

    protected function setUp(): void
    {
        parent::setUp(); // Call parent setUp for traits like RefreshDatabase
        $this->policy = new RatingPolicy();

        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->agency = Agency::factory()->create();
        $this->manager = User::factory()->create(['role' => 'Agency Manager', 'agency_id' => $this->agency->id]);
        $this->reporter = User::factory()->create(['role' => 'Reporter']);
        $this->otherReporter = User::factory()->create(['role' => 'Reporter']);

        $this->resolvedComplaint = Complaint::factory()->create([
            'user_id' => $this->reporter->id,
            'agency_id' => $this->agency->id,
            'status' => 'Resolved'
        ]);
        $this->pendingComplaint = Complaint::factory()->create([
            'user_id' => $this->reporter->id,
            'agency_id' => $this->agency->id,
            'status' => 'Pending'
        ]);
         $this->otherResolvedComplaint = Complaint::factory()->create([
            'user_id' => $this->otherReporter->id,
            'agency_id' => $this->agency->id,
            'status' => 'Resolved'
        ]);

        // Create a rating instance in DB for view/delete checks
        $this->rating = Rating::factory()->create([
            'user_id' => $this->reporter->id,
            'agency_id' => $this->agency->id,
            'complaint_id' => $this->resolvedComplaint->id,
        ]);
    }

    // viewAny (Admin only)
    public function test_admin_can_view_any_ratings(): void { $this->assertTrue($this->policy->viewAny($this->admin)); }
    public function test_manager_cannot_view_any_ratings(): void { $this->assertFalse($this->policy->viewAny($this->manager)); }
    public function test_reporter_cannot_view_any_ratings(): void { $this->assertFalse($this->policy->viewAny($this->reporter)); }

    // view
    public function test_admin_can_view_rating(): void { $this->assertTrue($this->policy->view($this->admin, $this->rating)); }
    public function test_reporter_can_view_own_rating(): void { $this->assertTrue($this->policy->view($this->reporter, $this->rating)); }
    public function test_manager_can_view_rating_for_their_agency(): void { $this->assertTrue($this->policy->view($this->manager, $this->rating)); }
    public function test_other_reporter_cannot_view_rating(): void { $this->assertFalse($this->policy->view($this->otherReporter, $this->rating)); }
    public function test_other_manager_cannot_view_rating(): void {
        $otherManager = User::factory()->create(['role' => 'Agency Manager']);
        $this->assertFalse($this->policy->view($otherManager, $this->rating));
    }


    // create (basic role check)
    public function test_reporter_passes_basic_create_check(): void { $this->assertTrue($this->policy->create($this->reporter)); }
    public function test_admin_fails_basic_create_check(): void { $this->assertFalse($this->policy->create($this->admin)); }
    public function test_manager_fails_basic_create_check(): void { $this->assertFalse($this->policy->create($this->manager)); }

    // rateComplaint (specific complaint check)
    public function test_reporter_can_rate_own_resolved_complaint_once(): void
    {
         // Delete the rating created in setUp to test the initial rate possibility
         $this->rating->delete();
        $this->assertTrue($this->policy->rateComplaint($this->reporter, $this->resolvedComplaint));
    }

     public function test_reporter_cannot_rate_pending_complaint(): void
     {
         $this->assertFalse($this->policy->rateComplaint($this->reporter, $this->pendingComplaint));
     }

      public function test_reporter_cannot_rate_others_resolved_complaint(): void
      {
          $this->assertFalse($this->policy->rateComplaint($this->reporter, $this->otherResolvedComplaint));
      }

       public function test_reporter_cannot_rate_complaint_twice(): void
       {
           // Rating already exists from setUp
           $this->assertFalse($this->policy->rateComplaint($this->reporter, $this->resolvedComplaint));
       }

       public function test_reporter_cannot_rate_complaint_with_no_agency(): void
       {
           $noAgencyComplaint = Complaint::factory()->create([
               'user_id' => $this->reporter->id,
               'agency_id' => null,
               'status' => 'Resolved'
           ]);
           $this->assertFalse($this->policy->rateComplaint($this->reporter, $noAgencyComplaint));
       }

    // update (always false)
    public function test_no_one_can_update_rating(): void
    {
        $this->assertFalse($this->policy->update($this->admin, $this->rating));
        $this->assertFalse($this->policy->update($this->reporter, $this->rating));
        $this->assertFalse($this->policy->update($this->manager, $this->rating));
    }

    // delete (Admin only)
    public function test_admin_can_delete_rating(): void { $this->assertTrue($this->policy->delete($this->admin, $this->rating)); }
    public function test_reporter_cannot_delete_rating(): void { $this->assertFalse($this->policy->delete($this->reporter, $this->rating)); }
    public function test_manager_cannot_delete_rating(): void { $this->assertFalse($this->policy->delete($this->manager, $this->rating)); }
}