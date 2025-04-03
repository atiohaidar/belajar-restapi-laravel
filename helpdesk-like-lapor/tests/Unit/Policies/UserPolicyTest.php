<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    protected UserPolicy $policy;
    protected User $admin;
    protected User $manager;
    protected User $reporter;
    protected User $otherUser; // A generic user model to be acted upon

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
        $this->admin = User::factory()->make(['id' => 'admin-uuid', 'role' => 'Admin']); // Assign fixed UUIDs for self-checks
        $this->manager = User::factory()->make(['id' => 'manager-uuid', 'role' => 'Agency Manager']);
        $this->reporter = User::factory()->make(['id' => 'reporter-uuid', 'role' => 'Reporter']);
        $this->otherUser = User::factory()->make(['id' => 'other-uuid', 'role' => 'Reporter']);
    }

    // viewAny
    public function test_admin_can_view_any_users(): void { $this->assertTrue($this->policy->viewAny($this->admin)); }
    public function test_manager_cannot_view_any_users(): void { $this->assertFalse($this->policy->viewAny($this->manager)); }
    public function test_reporter_cannot_view_any_users(): void { $this->assertFalse($this->policy->viewAny($this->reporter)); }

    // view
    public function test_admin_can_view_other_user(): void { $this->assertTrue($this->policy->view($this->admin, $this->otherUser)); }
    public function test_admin_can_view_self(): void { $this->assertTrue($this->policy->view($this->admin, $this->admin)); } // Admin can view self
    public function test_manager_cannot_view_other_user(): void { $this->assertFalse($this->policy->view($this->manager, $this->otherUser)); }
    public function test_reporter_cannot_view_other_user(): void { $this->assertFalse($this->policy->view($this->reporter, $this->otherUser)); }

    // create
    public function test_admin_can_create_user(): void { $this->assertTrue($this->policy->create($this->admin)); }
    public function test_manager_cannot_create_user(): void { $this->assertFalse($this->policy->create($this->manager)); }
    public function test_reporter_cannot_create_user(): void { $this->assertFalse($this->policy->create($this->reporter)); }

    // update
    public function test_admin_can_update_other_user(): void { $this->assertTrue($this->policy->update($this->admin, $this->otherUser)); }
    public function test_admin_can_update_self(): void { $this->assertTrue($this->policy->update($this->admin, $this->admin)); } // Admin can update self (controller might add restrictions)
    public function test_manager_cannot_update_other_user(): void { $this->assertFalse($this->policy->update($this->manager, $this->otherUser)); }
    public function test_reporter_cannot_update_other_user(): void { $this->assertFalse($this->policy->update($this->reporter, $this->otherUser)); }

    // delete
    public function test_admin_can_delete_other_user(): void { $this->assertTrue($this->policy->delete($this->admin, $this->otherUser)); }
    public function test_admin_cannot_delete_self(): void { $this->assertFalse($this->policy->delete($this->admin, $this->admin)); }
    public function test_manager_cannot_delete_other_user(): void { $this->assertFalse($this->policy->delete($this->manager, $this->otherUser)); }
    public function test_reporter_cannot_delete_other_user(): void { $this->assertFalse($this->policy->delete($this->reporter, $this->otherUser)); }
}