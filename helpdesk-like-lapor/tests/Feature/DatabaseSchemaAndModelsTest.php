<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Comment;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintCategory;
use App\Models\ComplaintFollowUp;
use App\Models\ComplaintLog;
use App\Models\ComplaintTransfer;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase; // Atau DatabaseMigrations
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSchemaAndModelsTest extends TestCase
{
    // Use RefreshDatabase (uses transactions, faster) or DatabaseMigrations (runs migrate:fresh)
    use RefreshDatabase;
    use WithFaker; // To use $this->faker if needed directly in tests

    // --- AGENCY ---

    public function test_agency_can_be_created(): void
    {
        $agency = Agency::factory()->create([
            'name' => 'Test Agency One',
            'email' => 'test@agency.one',
        ]);

        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'name' => 'Test Agency One',
            'email' => 'test@agency.one',
        ]);
        $this->assertInstanceOf(Agency::class, $agency);
    }

    public function test_agency_hierarchy_relationship(): void
    {
        $parent = Agency::factory()->create();
        $child = Agency::factory()->create(['parent_id' => $parent->id]);

        $this->assertDatabaseHas('agencies', ['id' => $child->id, 'parent_id' => $parent->id]);
        $this->assertInstanceOf(Agency::class, $child->parentAgency);
        $this->assertEquals($parent->id, $child->parentAgency->id);
        $this->assertTrue($parent->childAgencies->contains($child));
    }

    // --- COMPLAINT CATEGORY ---

    public function test_complaint_category_can_be_created(): void
    {
        $category = ComplaintCategory::factory()->create(['name' => 'Unique Category']);
        $this->assertDatabaseHas('complaint_categories', ['name' => 'Unique Category']);
        $this->assertInstanceOf(ComplaintCategory::class, $category);
    }

    // --- USER ---

    public function test_user_can_be_created(): void
    {
        $user = User::factory()->reporter()->create([
            'username' => 'testuser',
            'email' => 'test@user.com',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'test@user.com',
            'role' => 'Reporter',
        ]);
        $this->assertTrue(Hash::check('password', $user->password)); // Check default factory password
        $this->assertInstanceOf(User::class, $user);
    }

    public function test_user_belongs_to_agency_relationship(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->agencyManager($agency)->create(); // Use state

        $this->assertDatabaseHas('users', ['id' => $user->id, 'agency_id' => $agency->id]);
        $this->assertInstanceOf(Agency::class, $user->agency);
        $this->assertEquals($agency->id, $user->agency->id);
    }

    // --- COMPLAINT ---

    public function test_complaint_can_be_created_with_relationships(): void
    {
        // Factories will create the related models automatically
        $complaint = Complaint::factory()->create();

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id]);
        $this->assertInstanceOf(Complaint::class, $complaint);
        $this->assertInstanceOf(User::class, $complaint->user);
        $this->assertInstanceOf(Agency::class, $complaint->agency);
        $this->assertInstanceOf(ComplaintCategory::class, $complaint->category);
        // $this->assertEquals('Resolved', $complaint->fresh()->status); // Check default from migration if factory doesn't override
    }

     public function test_complaint_can_be_created_unassigned(): void
     {
         $complaint = Complaint::factory()->unassigned()->create();

         $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'agency_id' => null]);
         $this->assertNull($complaint->agency_id);
         $this->assertNull($complaint->agency); // Relationship should be null
         $this->assertEquals('Unprocessed', $complaint->status);
     }

    // --- COMPLAINT ATTACHMENT ---

    public function test_complaint_attachment_can_be_created(): void
    {
        $complaint = Complaint::factory()->create();
        $attachment = ComplaintAttachment::factory()->create([
            'complaint_id' => $complaint->id,
            'file_path' => '/uploads/test.jpg',
        ]);

        $this->assertDatabaseHas('complaint_attachments', [
            'id' => $attachment->id,
            'complaint_id' => $complaint->id,
            'file_path' => '/uploads/test.jpg',
        ]);
        $this->assertInstanceOf(ComplaintAttachment::class, $attachment);
        $this->assertEquals($complaint->id, $attachment->complaint->id);
    }

     public function test_complaint_attachment_cascade_delete_on_complaint_delete(): void
     {
         $attachment = ComplaintAttachment::factory()->create();
         $complaintId = $attachment->complaint_id;
         $attachmentId = $attachment->id;

         $this->assertDatabaseHas('complaints', ['id' => $complaintId]);
         $this->assertDatabaseHas('complaint_attachments', ['id' => $attachmentId]);

         Complaint::find($complaintId)->delete();

         $this->assertDatabaseMissing('complaints', ['id' => $complaintId]);
         $this->assertDatabaseMissing('complaint_attachments', ['id' => $attachmentId]); // Should be deleted due to cascade
     }

    // --- COMMENT ---

    public function test_comment_can_be_created(): void
    {
        $comment = Comment::factory()->create(); // Factories create User and Complaint

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertInstanceOf(Complaint::class, $comment->complaint);
    }

    public function test_comment_cascade_delete_on_complaint_delete(): void
    {
        $comment = Comment::factory()->create();
        $complaintId = $comment->complaint_id;
        $commentId = $comment->id;

        Complaint::find($complaintId)->delete();

        $this->assertDatabaseMissing('comments', ['id' => $commentId]);
    }

     public function test_comment_cascade_delete_on_user_delete(): void
     {
         $comment = Comment::factory()->create();
         $userId = $comment->user_id;
         $commentId = $comment->id;

         User::find($userId)->delete();

         $this->assertDatabaseMissing('comments', ['id' => $commentId]);
     }

    // --- NOTIFICATION ---

    public function test_notification_can_be_created(): void
    {
        $notification = Notification::factory()->create(); // Creates User, optional Complaint

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => false // Check default
        ]);
        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertInstanceOf(User::class, $notification->user);
        // Complaint might be null if factory allows it
        if ($notification->complaint_id) {
            $this->assertInstanceOf(Complaint::class, $notification->complaint);
        }
    }

    public function test_notification_cascade_delete_on_user_delete(): void
    {
        $notification = Notification::factory()->create();
        $userId = $notification->user_id;
        $notificationId = $notification->id;

        User::find($userId)->delete();

        $this->assertDatabaseMissing('notifications', ['id' => $notificationId]);
    }

    public function test_notification_set_null_on_complaint_delete(): void
    {
        // Ensure the factory creates a notification WITH a complaint_id
        $notification = Notification::factory()
                          ->for(Complaint::factory()) // Explicitly link to a new complaint
                          ->create();
        $complaintId = $notification->complaint_id;
        $notificationId = $notification->id;

        $this->assertNotNull($complaintId);
        $this->assertDatabaseHas('notifications', ['id' => $notificationId, 'complaint_id' => $complaintId]);

        Complaint::find($complaintId)->delete();

        $this->assertDatabaseMissing('complaints', ['id' => $complaintId]);
        // Notification should still exist, but complaint_id should be null
        $this->assertDatabaseHas('notifications', ['id' => $notificationId, 'complaint_id' => null]);
    }


    // --- COMPLAINT FOLLOW UP ---

    public function test_complaint_follow_up_can_be_created(): void
    {
        $followUp = ComplaintFollowUp::factory()->create(); // Creates Complaint, User, optional Agency

        $this->assertDatabaseHas('complaint_follow_ups', ['id' => $followUp->id]);
        $this->assertInstanceOf(ComplaintFollowUp::class, $followUp);
        $this->assertInstanceOf(Complaint::class, $followUp->complaint);
        $this->assertInstanceOf(User::class, $followUp->user);
        // Agency might be null if factory allows it
        if ($followUp->agency_id) {
            $this->assertInstanceOf(Agency::class, $followUp->agency);
        }
    }

     public function test_complaint_follow_up_cascade_on_complaint_delete(): void
     {
         $followUp = ComplaintFollowUp::factory()->create();
         $complaintId = $followUp->complaint_id;
         $followUpId = $followUp->id;

         Complaint::find($complaintId)->delete();
         $this->assertDatabaseMissing('complaint_follow_ups', ['id' => $followUpId]);
     }

     public function test_complaint_follow_up_cascade_on_user_delete(): void
     {
         $followUp = ComplaintFollowUp::factory()->create();
         $userId = $followUp->user_id;
         $followUpId = $followUp->id;

         User::find($userId)->delete();
         $this->assertDatabaseMissing('complaint_follow_ups', ['id' => $followUpId]);
     }

    // --- COMPLAINT TRANSFER ---

    public function test_complaint_transfer_can_be_created(): void
    {
        $transfer = ComplaintTransfer::factory()->create(); // Creates Complaint, Agencies, User

        $this->assertDatabaseHas('complaint_transfers', ['id' => $transfer->id]);
        $this->assertInstanceOf(ComplaintTransfer::class, $transfer);
        $this->assertInstanceOf(Complaint::class, $transfer->complaint);
        $this->assertInstanceOf(Agency::class, $transfer->fromAgency);
        $this->assertInstanceOf(Agency::class, $transfer->toAgency);
        $this->assertInstanceOf(User::class, $transfer->user);
    }

     public function test_complaint_transfer_set_null_on_user_delete(): void
     {
         $transfer = ComplaintTransfer::factory()->create();
         $userId = $transfer->user_id;
         $transferId = $transfer->id;
         User::find($userId)->delete();

         $this->assertDatabaseHas('complaint_transfers', ['id' => $transferId, 'user_id' => null]);
     }

     // Add tests for agency deletion (cascade/set null depending on migration choice) if needed

    // --- RATING ---

    public function test_rating_can_be_created(): void
    {
        $rating = Rating::factory()->create(['stars' => 4]); // Creates User, Agency, optional Complaint

        $this->assertDatabaseHas('ratings', ['id' => $rating->id, 'stars' => 4]);
        $this->assertInstanceOf(Rating::class, $rating);
        $this->assertInstanceOf(User::class, $rating->user);
        $this->assertInstanceOf(Agency::class, $rating->agency);
         if ($rating->complaint_id) {
             $this->assertInstanceOf(Complaint::class, $rating->complaint);
         }
    }

    // Add tests for user/agency/complaint deletion effects on Rating based on migration constraints

    // --- COMPLAINT LOG ---

    public function test_complaint_log_can_be_created(): void
    {
        $log = ComplaintLog::factory()->create(); // Creates Complaint, optional User

        $this->assertDatabaseHas('complaint_logs', ['id' => $log->id]);
        $this->assertInstanceOf(ComplaintLog::class, $log);
        $this->assertInstanceOf(Complaint::class, $log->complaint);
        if ($log->user_id) {
             $this->assertInstanceOf(User::class, $log->user);
         }
    }

     public function test_complaint_log_cascade_on_complaint_delete(): void
     {
         $log = ComplaintLog::factory()->create();
         $complaintId = $log->complaint_id;
         $logId = $log->id;

         Complaint::find($complaintId)->delete();
         $this->assertDatabaseMissing('complaint_logs', ['id' => $logId]);
     }

      public function test_complaint_log_set_null_on_user_delete(): void
      {
          // Ensure log has a user_id
          $log = ComplaintLog::factory()->for(User::factory())->create();
          $userId = $log->user_id;
          $logId = $log->id;

          $this->assertNotNull($userId);
          $this->assertDatabaseHas('complaint_logs', ['id' => $logId, 'user_id' => $userId]);

          User::find($userId)->delete();
          $this->assertDatabaseHas('complaint_logs', ['id' => $logId, 'user_id' => null]);
      }

}