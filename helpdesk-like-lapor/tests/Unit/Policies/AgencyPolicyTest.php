<?php

namespace Tests\Unit\Policies;

use App\Models\Agency;
use App\Models\User;
use App\Policies\AgencyPolicy;
use Tests\TestCase;

class AgencyPolicyTest extends TestCase
{
    protected AgencyPolicy $policy;
    protected User $admin;
    protected User $manager;
    protected User $reporter;
    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AgencyPolicy();
        $this->admin = User::factory()->make(['role' => 'Admin']);
        $this->manager = User::factory()->make(['role' => 'Agency Manager']);
        $this->reporter = User::factory()->make(['role' => 'Reporter']);
        $this->agency = Agency::factory()->make();
    }

    // viewAny & view (Allow any authenticated user)
    public function test_authenticated_user_can_view_any_agencies(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
        $this->assertTrue($this->policy->viewAny($this->manager));
        $this->assertTrue($this->policy->viewAny($this->reporter));
    }

    public function test_unauthenticated_user_cannot_view_any_agencies(): void
    {
        $this->assertFalse($this->policy->viewAny(null));
    }

    public function test_authenticated_user_can_view_agency(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->agency));
        $this->assertTrue($this->policy->view($this->manager, $this->agency));
        $this->assertTrue($this->policy->view($this->reporter, $this->agency));
    }

     public function test_unauthenticated_user_cannot_view_agency(): void
     {
         $this->assertFalse($this->policy->view(null, $this->agency));
     }


    // create, update, delete (Admin only)
    public function test_admin_can_create_agency(): void { $this->assertTrue($this->policy->create($this->admin)); }
    public function test_manager_cannot_create_agency(): void { $this->assertFalse($this->policy->create($this->manager)); }
    public function test_reporter_cannot_create_agency(): void { $this->assertFalse($this->policy->create($this->reporter)); }

    public function test_admin_can_update_agency(): void { $this->assertTrue($this->policy->update($this->admin, $this->agency)); }
    public function test_manager_cannot_update_agency(): void { $this->assertFalse($this->policy->update($this->manager, $this->agency)); }
    public function test_reporter_cannot_update_agency(): void { $this->assertFalse($this->policy->update($this->reporter, $this->agency)); }

    public function test_admin_can_delete_agency(): void { $this->assertTrue($this->policy->delete($this->admin, $this->agency)); }
    public function test_manager_cannot_delete_agency(): void { $this->assertFalse($this->policy->delete($this->manager, $this->agency)); }
    public function test_reporter_cannot_delete_agency(): void { $this->assertFalse($this->policy->delete($this->reporter, $this->agency)); }
}