<?php

namespace Tests\Unit\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Policies\CommentPolicy;
use Tests\TestCase;

class CommentPolicyTest extends TestCase
{
    protected CommentPolicy $policy;
    protected User $admin;
    protected User $reporter; // User who wrote the comment
    protected User $otherUser; // Another user
    protected Comment $comment; // Comment written by $reporter

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CommentPolicy();
        $this->admin = User::factory()->make(['role' => 'Admin']);
        $this->reporter = User::factory()->make(['role' => 'Reporter']);
        $this->otherUser = User::factory()->make(['role' => 'Agency Manager']);
        // Create comment instance linked to the reporter
        $this->comment = Comment::factory()->make(['user_id' => $this->reporter->id]);
    }

    // update (should always be false)
    public function test_no_one_can_update_comment(): void
    {
        $this->assertFalse($this->policy->update($this->admin, $this->comment));
        $this->assertFalse($this->policy->update($this->reporter, $this->comment));
        $this->assertFalse($this->policy->update($this->otherUser, $this->comment));
    }

    // delete
    public function test_admin_can_delete_any_comment(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->comment));
    }

     public function test_user_can_delete_their_own_comment(): void
     {
         $this->assertTrue($this->policy->delete($this->reporter, $this->comment));
     }

      public function test_user_cannot_delete_others_comment(): void
      {
          $this->assertFalse($this->policy->delete($this->otherUser, $this->comment));
      }
}