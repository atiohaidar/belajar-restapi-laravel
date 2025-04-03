<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgencyApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $reporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->manager = User::factory()->create(['role' => 'Agency Manager']);
        $this->reporter = User::factory()->create(['role' => 'Reporter']);
    }

    // --- INDEX ---
    public function test_authenticated_users_can_list_agencies(): void
    {
        Agency::factory()->count(3)->create();

        foreach ([$this->admin, $this->manager, $this->reporter] as $user) {
            Sanctum::actingAs($user);
            $response = $this->getJson(route('agencies.index'));
            $response->assertStatus(200)->assertJsonCount(3, 'data');
        }
    }

    public function test_index_can_filter_top_level_agencies(): void
    {
        $parent = Agency::factory()->create();
        Agency::factory()->create(['parent_id' => $parent->id]); // Child
        Agency::factory()->create(); // Another top-level

        Sanctum::actingAs($this->admin);
        $response = $this->getJson(route('agencies.index', ['top_level_only' => true]));

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data') // Only the two top-level agencies
                 ->assertJsonFragment(['id' => $parent->id])
                 ->assertJsonMissing(['id' => $parent->childAgencies()->first()->id]);
    }

     public function test_unauthenticated_user_cannot_list_agencies(): void
     {
         $response = $this->getJson(route('agencies.index'));
         $response->assertStatus(401);
     }

    // --- STORE ---
    public function test_admin_can_create_agency(): void
    {
        Sanctum::actingAs($this->admin);
        $parent = Agency::factory()->create();
        $data = [
            'name' => 'New Sub Agency',
            'email' => 'sub@agency.test',
            'parent_id' => $parent->id,
        ];
        $response = $this->postJson(route('agencies.store'), $data);
        $response->assertStatus(201)->assertJsonFragment(['name' => 'New Sub Agency', 'parent_id' => $parent->id]);
        $this->assertDatabaseHas('agencies', $data);
    }

    public function test_non_admin_cannot_create_agency(): void
    {
        $data = ['name' => 'Attempt Agency'];
        foreach ([$this->manager, $this->reporter] as $user) {
            Sanctum::actingAs($user);
            $response = $this->postJson(route('agencies.store'), $data);
            $response->assertStatus(403);
        }
    }

    public function test_create_agency_validation(): void
    {
        Sanctum::actingAs($this->admin);
        // Missing name
        $res1 = $this->postJson(route('agencies.store'), ['email' => 'test@test.com']);
        $res1->assertStatus(422)->assertJsonValidationErrors(['name']);
        // Invalid parent_id
        $res2 = $this->postJson(route('agencies.store'), ['name' => 'Test', 'parent_id' => 'invalid-uuid']);
        $res2->assertStatus(422)->assertJsonValidationErrors(['parent_id']);
        // Invalid email
         $res3 = $this->postJson(route('agencies.store'), ['name' => 'Test', 'email' => 'not-an-email']);
         $res3->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    // --- SHOW ---
    public function test_authenticated_users_can_view_agency(): void
    {
        $agency = Agency::factory()->create();
         foreach ([$this->admin, $this->manager, $this->reporter] as $user) {
            Sanctum::actingAs($user);
            $response = $this->getJson(route('agencies.show', $agency));
            $response->assertStatus(200)->assertJsonFragment(['id' => $agency->id]);
        }
    }

     public function test_show_agency_can_include_relations(): void
     {
         $parent = Agency::factory()->create();
         $child = Agency::factory()->create(['parent_id' => $parent->id]);
         Sanctum::actingAs($this->admin);

         // Include parent
         $res1 = $this->getJson(route('agencies.show', [$child, 'include_parent' => true]));
         $res1->assertStatus(200)->assertJsonPath('data.parent_agency.id', $parent->id);

         // Include children
         $res2 = $this->getJson(route('agencies.show', [$parent, 'include_children' => true]));
         $res2->assertStatus(200)->assertJsonCount(1, 'data.child_agencies'); // Assuming only one child was created
         $res2->assertJsonPath('data.child_agencies.0.id', $child->id);
     }

    public function test_show_agency_404(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->getJson(route('agencies.show', 'non-existent-uuid'));
        $response->assertStatus(404);
    }

     public function test_unauthenticated_user_cannot_view_agency(): void
     {
         $agency = Agency::factory()->create();
         $response = $this->getJson(route('agencies.show', $agency));
         $response->assertStatus(401);
     }

    // --- UPDATE ---
    public function test_admin_can_update_agency(): void
    {
        $agency = Agency::factory()->create();
        $parent = Agency::factory()->create();
        Sanctum::actingAs($this->admin);
        $data = ['name' => 'Updated Agency Name', 'parent_id' => $parent->id];

        $response = $this->putJson(route('agencies.update', $agency), $data);
        $response->assertStatus(200)->assertJsonFragment(['name' => 'Updated Agency Name', 'parent_id' => $parent->id]);
        $this->assertDatabaseHas('agencies', ['id' => $agency->id, 'name' => 'Updated Agency Name']);
    }

    public function test_non_admin_cannot_update_agency(): void
    {
        $agency = Agency::factory()->create();
        $data = ['name' => 'Update Attempt'];
         foreach ([$this->manager, $this->reporter] as $user) {
            Sanctum::actingAs($user);
            $response = $this->putJson(route('agencies.update', $agency), $data);
            $response->assertStatus(403);
        }
    }

    public function test_update_agency_validation(): void
    {
         $agency1 = Agency::factory()->create(['email' => 'one@test.com']);
         $agency2 = Agency::factory()->create(['email' => 'two@test.com']);
         Sanctum::actingAs($this->admin);

         // Empty name
         $res1 = $this->putJson(route('agencies.update', $agency1), ['name' => '']);
         $res1->assertStatus(422)->assertJsonValidationErrors(['name']);
         // Duplicate email
         $res2 = $this->putJson(route('agencies.update', $agency2), ['email' => 'one@test.com']);
         $res2->assertStatus(422)->assertJsonValidationErrors(['email']);
          // Set self as parent
          $res3 = $this->putJson(route('agencies.update', $agency1), ['parent_id' => $agency1->id]);
          $res3->assertStatus(422)->assertJsonValidationErrors(['parent_id']);
    }

    // --- DELETE ---
    public function test_admin_can_delete_agency(): void
    {
        $agency = Agency::factory()->create();
        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson(route('agencies.destroy', $agency));
        $response->assertStatus(204);
        $this->assertDatabaseMissing('agencies', ['id' => $agency->id]);
    }

    public function test_non_admin_cannot_delete_agency(): void
    {
        $agency = Agency::factory()->create();
         foreach ([$this->manager, $this->reporter] as $user) {
            Sanctum::actingAs($user);
            $response = $this->deleteJson(route('agencies.destroy', $agency));
            $response->assertStatus(403);
        }
    }

    public function test_cannot_delete_agency_with_relations(): void
    {
         Sanctum::actingAs($this->admin);

         // With User
         $agency1 = Agency::factory()->create();
         User::factory()->create(['agency_id' => $agency1->id]);
         $res1 = $this->deleteJson(route('agencies.destroy', $agency1));
         $res1->assertStatus(409)->assertJsonFragment(['message' => 'Cannot delete agency with assigned users.']);
         $this->assertDatabaseHas('agencies', ['id' => $agency1->id]);

         // With Complaint
         $agency2 = Agency::factory()->create();
         Complaint::factory()->create(['agency_id' => $agency2->id]);
         $res2 = $this->deleteJson(route('agencies.destroy', $agency2));
         $res2->assertStatus(409)->assertJsonFragment(['message' => 'Cannot delete agency with assigned complaints.']);
         $this->assertDatabaseHas('agencies', ['id' => $agency2->id]);

         // With Child Agency
         $agency3 = Agency::factory()->create();
         Agency::factory()->create(['parent_id' => $agency3->id]);
         $res3 = $this->deleteJson(route('agencies.destroy', $agency3));
         $res3->assertStatus(409)->assertJsonFragment(['message' => 'Cannot delete agency with sub-agencies.']);
         $this->assertDatabaseHas('agencies', ['id' => $agency3->id]);
    }

    public function test_delete_agency_404(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson(route('agencies.destroy', 'non-existent-uuid'));
        $response->assertStatus(404);
    }
}