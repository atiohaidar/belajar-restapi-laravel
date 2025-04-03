<?php

namespace Tests\Unit\Policies;

use App\Models\ComplaintCategory;
use App\Models\User;
use App\Policies\ComplaintCategoryPolicy;
use Tests\TestCase;

class ComplaintCategoryPolicyTest extends TestCase
{
    protected ComplaintCategoryPolicy $policy;
    protected User $admin;
    protected User $manager;
    protected User $reporter;
    protected ComplaintCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ComplaintCategoryPolicy();
        $this->admin = User::factory()->make(['role' => 'Admin']);
        $this->manager = User::factory()->make(['role' => 'Agency Manager']);
        $this->reporter = User::factory()->make(['role' => 'Reporter']);
        $this->category = ComplaintCategory::factory()->make(); // Doesn't need DB
    }

    // Test viewAny
    public function test_admin_can_view_any_categories(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_manager_can_view_any_categories(): void
    {
        $this->assertTrue($this->policy->viewAny($this->manager));
    }

    public function test_reporter_cannot_view_any_categories(): void
    {
        $this->assertFalse($this->policy->viewAny($this->reporter));
    }

    // Test view
    public function test_admin_can_view_category(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->category));
    }

     public function test_manager_can_view_category(): void
     {
         $this->assertTrue($this->policy->view($this->manager, $this->category));
     }

     public function test_reporter_cannot_view_category(): void
     {
         $this->assertFalse($this->policy->view($this->reporter, $this->category));
     }

    // Test create
    public function test_admin_can_create_category(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_manager_cannot_create_category(): void
    {
        $this->assertFalse($this->policy->create($this->manager));
    }

    public function test_reporter_cannot_create_category(): void
    {
        $this->assertFalse($this->policy->create($this->reporter));
    }

    // Test update
    public function test_admin_can_update_category(): void
    {
        // $this->assertTrue(false);
        $this->assertTrue($this->policy->update($this->admin, $this->category));
    }

     public function test_manager_cannot_update_category(): void
     {
         $this->assertFalse($this->policy->update($this->manager, $this->category));
     }

     public function test_reporter_cannot_update_category(): void
     {
         $this->assertFalse($this->policy->update($this->reporter, $this->category));
     }

     // Test delete
     public function test_admin_can_delete_category(): void
     {
         $this->assertTrue($this->policy->delete($this->admin, $this->category));
     }

      public function test_manager_cannot_delete_category(): void
      {
          $this->assertFalse($this->policy->delete($this->manager, $this->category));
      }

      public function test_reporter_cannot_delete_category(): void
      {
          $this->assertFalse($this->policy->delete($this->reporter, $this->category));
      }
}