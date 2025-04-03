<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\Comment;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected Agency $managerAgency;
    protected User $reporter;
    protected Complaint $complaint; // A generic complaint

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->managerAgency = Agency::factory()->create();
        $this->manager = User::factory()->create(['role' => 'Agency Manager', 'agency_id' => $this->managerAgency->id]);
        $this->reporter = User::factory()->create(['role' => 'Reporter']);

        // Create a complaint that reporter and manager can interact with
        $this->complaint = Complaint::factory()->create([
            'user_id' => $this->reporter->id,
            'agency_id' => $this->managerAgency->id,
            'status' => 'Pending' // Ensure it's not archived
        ]);
    }

    // --- STORE COMMENT ---

    public function test_reporter_can_add_comment_to_own_complaint(): void
    {
        Sanctum::actingAs($this->reporter);
        $data = ['message' => 'This is my update on the issue.'];

        $response = $this->postJson(route('complaints.comments.store', $this->complaint), $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['message' => $data['message']])
                 ->assertJsonPath('data.user.id', $this->reporter->id);

        $this->assertDatabaseHas('comments', [
            'complaint_id' => $this->complaint->id,
            'user_id' => $this->reporter->id,
            'message' => $data['message'],
        ]);
        // Check log created
        $this->assertDatabaseHas('complaint_logs', [
            'complaint_id' => $this->complaint->id,
            'action' => 'Comment Added',
            'user_id' => $this->reporter->id
        ]);
    }

    public function test_manager_can_add_comment_to_assigned_complaint(): void
    {
        Sanctum::actingAs($this->manager);
        $data = ['message' => 'We are looking into this.'];

        $response = $this->postJson(route('complaints.comments.store', $this->complaint), $data);

        $response->assertStatus(201)
                 ->assertJsonPath('data.user.id', $this->manager->id);
        $this->assertDatabaseHas('comments', ['message' => $data['message'], 'user_id' => $this->manager->id]);
        $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $this->complaint->id, 'action' => 'Comment Added', 'user_id' => $this->manager->id]);
    }

     public function test_admin_can_add_comment_to_any_complaint(): void
     {
         Sanctum::actingAs($this->admin);
         $data = ['message' => 'Admin comment added.'];
         $response = $this->postJson(route('complaints.comments.store', $this->complaint), $data);
         $response->assertStatus(201)->assertJsonPath('data.user.id', $this->admin->id);
         $this->assertDatabaseHas('comments', ['message' => $data['message'], 'user_id' => $this->admin->id]);
         $this->assertDatabaseHas('complaint_logs', ['complaint_id' => $this->complaint->id, 'action' => 'Comment Added', 'user_id' => $this->admin->id]);
     }


    public function test_unauthorized_user_cannot_add_comment(): void
    {
        $otherReporter = User::factory()->create(['role' => 'Reporter']);
        $otherManager = User::factory()->create(['role' => 'Agency Manager', 'agency_id' => Agency::factory()->create()->id]);
        $data = ['message' => 'Unauthorized comment.'];

        // Unauthenticated cannot comment
        $this->postJson(route('complaints.comments.store', $this->complaint), $data)->assertStatus(401);
        // Other reporter cannot comment
        Sanctum::actingAs($otherReporter);
        $this->postJson(route('complaints.comments.store', $this->complaint), $data)->assertStatus(403);

        // Other manager cannot comment
        Sanctum::actingAs($otherManager);
        $this->postJson(route('complaints.comments.store', $this->complaint), $data)->assertStatus(403);

    }

     public function test_cannot_add_comment_to_archived_complaint(): void
     {
         $this->complaint->update(['status' => 'Archived']);
         $data = ['message' => 'Trying to comment on archived.'];

         Sanctum::actingAs($this->reporter); // Reporter should be blocked
         $this->postJson(route('complaints.comments.store', $this->complaint), $data)->assertStatus(403);

         Sanctum::actingAs($this->manager); // Manager should be blocked
         $this->postJson(route('complaints.comments.store', $this->complaint), $data)->assertStatus(403);

         // Admin might still be allowed based on policy, test that if needed
         // Sanctum::actingAs($this->admin);
         // $this->postJson(route('complaints.comments.store', $this->complaint), $data)->assertStatus(201); // Or 403 if policy forbids
     }

    public function test_store_comment_validation(): void
    {
        Sanctum::actingAs($this->reporter);
        // Missing message
        $res1 = $this->postJson(route('complaints.comments.store', $this->complaint), []);
        $res1->assertStatus(422)->assertJsonValidationErrors(['message']);
        // Message too short
        $res2 = $this->postJson(route('complaints.comments.store', $this->complaint), ['message' => 'Hi']);
        $res2->assertStatus(422)->assertJsonValidationErrors(['message']);
    }

    // --- DELETE COMMENT ---

    public function test_admin_can_delete_any_comment(): void
    {
        $comment = Comment::factory()->create([
            'complaint_id' => $this->complaint->id,
            'user_id' => $this->reporter->id
        ]);
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson(route('comments.destroy', $comment));
        $response->assertStatus(204);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        // Check log created
         $this->assertDatabaseHas('complaint_logs', [
             'complaint_id' => $this->complaint->id,
             'action' => 'Comment Deleted',
             'user_id' => $this->admin->id, // Logged by admin
             // Check details contain original author info
         ]);
         // Check details using assertDatabaseHas requires exact match or use DB::table()...->where(...)->first()->details
         $log = \App\Models\ComplaintLog::where('complaint_id', $this->complaint->id)->where('action', 'Comment Deleted')->latest('timestamp')->first();
         $this->assertStringContainsString("Comment by {$this->reporter->name} deleted.", $log->details);
         $this->assertStringContainsString("(Deleted by: {$this->admin->name})", $log->details);


    }

    public function test_user_can_delete_own_comment(): void
    {
         $comment = Comment::factory()->create([
             'complaint_id' => $this->complaint->id,
             'user_id' => $this->reporter->id
         ]);
         Sanctum::actingAs($this->reporter);

         $response = $this->deleteJson(route('comments.destroy', $comment));
         $response->assertStatus(204);
         $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
          // Check log created
          $this->assertDatabaseHas('complaint_logs', [
              'complaint_id' => $this->complaint->id,
              'action' => 'Comment Deleted',
              'user_id' => $this->reporter->id // Logged by reporter (who deleted)
          ]);
          $log = \App\Models\ComplaintLog::where('complaint_id', $this->complaint->id)->where('action', 'Comment Deleted')->latest('timestamp')->first();
          $this->assertStringContainsString("Comment by {$this->reporter->name} deleted.", $log->details);
          // Should not contain the "(Deleted by: ...)" part if self-deleted
          $this->assertStringNotContainsString("(Deleted by:", $log->details);
    }

     public function test_user_cannot_delete_others_comment(): void
     {
         $comment = Comment::factory()->create([
             'complaint_id' => $this->complaint->id,
             'user_id' => $this->reporter->id // Comment by reporter
         ]);

         // Manager tries to delete reporter's comment
         Sanctum::actingAs($this->manager);
         $response = $this->deleteJson(route('comments.destroy', $comment));
         $response->assertStatus(403); // Forbidden by policy

         $this->assertDatabaseHas('comments', ['id' => $comment->id]); // Ensure not deleted
     }

      public function test_delete_comment_returns_404_if_not_found(): void
      {
          Sanctum::actingAs($this->admin);
          $response = $this->deleteJson(route('comments.destroy', 'non-existent-uuid'));
          $response->assertStatus(404);
      }

}