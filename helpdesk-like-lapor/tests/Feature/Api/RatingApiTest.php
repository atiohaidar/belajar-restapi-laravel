<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RatingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $reporter;
    protected User $otherReporter;
    protected Agency $agency;
    protected Complaint $resolvedComplaint; // Resolved, by reporter, for agency
    protected Complaint $pendingComplaint; // Pending, by reporter, for agency

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->reporter = User::factory()->create(['role' => 'Reporter']);
        $this->otherReporter = User::factory()->create(['role' => 'Reporter']);
        $this->agency = Agency::factory()->create(['name' => 'Test Agency']);

        $this->resolvedComplaint = Complaint::factory()->create([
            'user_id' => $this->reporter->id,
            'agency_id' => $this->agency->id,
            'status' => 'Resolved',
        ]);
        $this->pendingComplaint = Complaint::factory()->create([
            'user_id' => $this->reporter->id,
            'agency_id' => $this->agency->id,
            'status' => 'Pending',
        ]);
    }

    // --- STORE ---
    public function test_reporter_can_rate_own_resolved_complaint(): void
    {
        Sanctum::actingAs($this->reporter);
        $data = [
            'complaint_id' => $this->resolvedComplaint->id,
            'stars' => 4,
            'review' => 'Service was good, but took a while.',
        ];

        $response = $this->postJson(route('ratings.store'), $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['stars' => 4])
                 ->assertJsonPath('data.user.id', $this->reporter->id)
                 ->assertJsonPath('data.agency.id', $this->agency->id)
                 ->assertJsonPath('data.complaint_id', $this->resolvedComplaint->id);

        $this->assertDatabaseHas('ratings', [
            'user_id' => $this->reporter->id,
            'agency_id' => $this->agency->id,
            'complaint_id' => $this->resolvedComplaint->id,
            'stars' => 4,
        ]);
    }

    public function test_reporter_cannot_rate_pending_complaint(): void
    {
         Sanctum::actingAs($this->reporter);
         $data = ['complaint_id' => $this->pendingComplaint->id, 'stars' => 5];
         $response = $this->postJson(route('ratings.store'), $data);
         // Authorization in StoreRatingRequest should fail
         $response->assertStatus(403);
         $this->assertDatabaseMissing('ratings', ['complaint_id' => $this->pendingComplaint->id]);
    }

    public function test_reporter_cannot_rate_complaint_twice(): void
    {
        // Create initial rating
        Rating::factory()->create([
             'user_id' => $this->reporter->id,
             'complaint_id' => $this->resolvedComplaint->id,
             'agency_id' => $this->agency->id,
        ]);

        Sanctum::actingAs($this->reporter);
        $data = ['complaint_id' => $this->resolvedComplaint->id, 'stars' => 3];
        $response = $this->postJson(route('ratings.store'), $data);
         // Authorization in StoreRatingRequest should fail (policy check)
        $response->assertStatus(403);
    }

     public function test_reporter_cannot_rate_others_complaint(): void
     {
        $otherResolved = Complaint::factory()->create(['user_id' => $this->otherReporter->id, 'status' => 'Resolved']);
         Sanctum::actingAs($this->reporter);
         $data = ['complaint_id' => $otherResolved->id, 'stars' => 5];
         $response = $this->postJson(route('ratings.store'), $data);
         $response->assertStatus(403); // Forbidden by policy check in authorize
     }


    public function test_non_reporter_cannot_rate_complaint(): void
    {
        $manager = User::factory()->create(['role' => 'Agency Manager']);
        $data = ['complaint_id' => $this->resolvedComplaint->id, 'stars' => 5];

        Sanctum::actingAs($this->admin);
        $this->postJson(route('ratings.store'), $data)->assertStatus(403);

        Sanctum::actingAs($manager);
        $this->postJson(route('ratings.store'), $data)->assertStatus(403);
    }

    public function test_store_rating_validation(): void
    {
        Sanctum::actingAs($this->reporter);
        // Missing complaint_id
        $res1 = $this->postJson(route('ratings.store'), ['stars' => 5]);
        $res1->assertStatus(422)->assertJsonValidationErrors(['complaint_id']);
        // Missing stars
        $res2 = $this->postJson(route('ratings.store'), ['complaint_id' => $this->resolvedComplaint->id]);
        $res2->assertStatus(422)->assertJsonValidationErrors(['stars']);
        // Invalid stars
        $res3 = $this->postJson(route('ratings.store'), ['complaint_id' => $this->resolvedComplaint->id, 'stars' => 6]);
        $res3->assertStatus(422)->assertJsonValidationErrors(['stars']);
         $res4 = $this->postJson(route('ratings.store'), ['complaint_id' => $this->resolvedComplaint->id, 'stars' => 0]);
         $res4->assertStatus(422)->assertJsonValidationErrors(['stars']);
    }

    // --- INDEX (Admin Only) ---
    public function test_admin_can_list_ratings_with_filters(): void
    {
        $agency2 = Agency::factory()->create();
        Rating::factory()->count(3)->create(['agency_id' => $this->agency->id, 'stars' => 5]);
        Rating::factory()->count(2)->create(['agency_id' => $agency2->id, 'stars' => 3]);

        Sanctum::actingAs($this->admin);

        // All ratings
        $resAll = $this->getJson(route('ratings.index'));
        $resAll->assertStatus(200)->assertJsonCount(5, 'data'); // Assuming default pagination > 5

        // Filter by agency
        $resAgen = $this->getJson(route('ratings.index', ['agency_id' => $this->agency->id]));
        $resAgen->assertStatus(200);
        // Check all returned ratings belong to the agency
        foreach ($resAgen->json('data') as $rating) {
             $this->assertEquals($this->agency->id, $rating['agency']['id']);
        }
         $this->assertGreaterThanOrEqual(3, $resAgen->json('meta.total'));

        // Filter by stars
        $resStars = $this->getJson(route('ratings.index', ['stars' => 3]));
        $resStars->assertStatus(200);
         foreach ($resStars->json('data') as $rating) {
              $this->assertEquals(3, $rating['stars']);
         }
         $this->assertGreaterThanOrEqual(2, $resStars->json('meta.total'));
    }

    public function test_non_admin_cannot_list_ratings(): void
    {
         Rating::factory()->count(1)->create();
         $manager = User::factory()->create(['role' => 'Agency Manager']);
         Sanctum::actingAs($this->reporter);
         $this->getJson(route('ratings.index'))->assertStatus(403);
         Sanctum::actingAs($manager);
          $this->getJson(route('ratings.index'))->assertStatus(403);
    }


    // --- SHOW (Admin, Reporter Owner, Agency Manager) ---
    public function test_authorized_users_can_view_rating(): void
    {
        $rating = Rating::factory()->create([
             'user_id' => $this->reporter->id,
             'agency_id' => $this->agency->id,
             'complaint_id' => $this->resolvedComplaint->id,
        ]);
        $manager = User::factory()->create(['role' => 'Agency Manager', 'agency_id' => $this->agency->id]);

        // Admin
        Sanctum::actingAs($this->admin);
        $this->getJson(route('ratings.show', $rating))->assertStatus(200)->assertJsonPath('data.id', $rating->id);

        // Reporter (owner)
        Sanctum::actingAs($this->reporter);
        $this->getJson(route('ratings.show', $rating))->assertStatus(200)->assertJsonPath('data.id', $rating->id);

         // Manager of rated agency
         Sanctum::actingAs($manager);
         $this->getJson(route('ratings.show', $rating))->assertStatus(200)->assertJsonPath('data.id', $rating->id);
    }

    public function test_unauthorized_users_cannot_view_rating(): void
    {
        $rating = Rating::factory()->create();
        $otherReporter = User::factory()->create(['role'=>'Reporter']);
        $otherManager = User::factory()->create(['role'=>'Agency Manager']);

        Sanctum::actingAs($otherReporter);
         $this->getJson(route('ratings.show', $rating))->assertStatus(403);

        Sanctum::actingAs($otherManager);
         $this->getJson(route('ratings.show', $rating))->assertStatus(403);
    }


    // --- DELETE (Admin Only) ---
    public function test_admin_can_delete_rating(): void
    {
         $rating = Rating::factory()->create();
         Sanctum::actingAs($this->admin);
         $response = $this->deleteJson(route('ratings.destroy', $rating));
         $response->assertStatus(204);
         $this->assertDatabaseMissing('ratings', ['id' => $rating->id]);
    }

     public function test_non_admin_cannot_delete_rating(): void
     {
         $rating = Rating::factory()->create(['user_id' => $this->reporter->id]);
         $manager = User::factory()->create(['role' => 'Agency Manager']);

         Sanctum::actingAs($this->reporter); // Reporter (owner) cannot delete
         $this->deleteJson(route('ratings.destroy', $rating))->assertStatus(403);

         Sanctum::actingAs($manager); // Manager cannot delete
         $this->deleteJson(route('ratings.destroy', $rating))->assertStatus(403);

         $this->assertDatabaseHas('ratings', ['id' => $rating->id]);
     }

}