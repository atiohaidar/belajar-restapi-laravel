<?php

namespace Tests\Feature\Api;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplaintCategoryApiTest extends TestCase
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
    public function test_admin_can_list_categories(): void
    {
        ComplaintCategory::factory()->count(3)->create();
        Sanctum::actingAs($this->admin);

        $response = $this->getJson(route('complaint-categories.index'));

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data'); // Check resource collection structure
    }

    public function test_manager_can_list_categories(): void
    {
        ComplaintCategory::factory()->count(2)->create();
        Sanctum::actingAs($this->manager);

        $response = $this->getJson(route('complaint-categories.index'));

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

     public function test_reporter_cannot_list_categories(): void
     {
         ComplaintCategory::factory()->count(1)->create();
         Sanctum::actingAs($this->reporter);

         $response = $this->getJson(route('complaint-categories.index'));

         $response->assertStatus(403); // Forbidden by Policy
     }

      public function test_unauthenticated_user_cannot_list_categories(): void
      {
          $response = $this->getJson(route('complaint-categories.index'));
          $response->assertStatus(401); // Unauthorized
      }


    // --- STORE ---
    public function test_admin_can_create_category(): void
    {
        Sanctum::actingAs($this->admin);
        $data = [
            'name' => 'New Test Category',
            'description' => 'A description.',
        ];

        $response = $this->postJson(route('complaint-categories.store'), $data);

        $response->assertStatus(201) // Should be 201 Created
                 ->assertJsonFragment(['name' => 'New Test Category']);
        $this->assertDatabaseHas('complaint_categories', $data);
    }

    public function test_manager_cannot_create_category(): void
    {
        Sanctum::actingAs($this->manager);
        $data = ['name' => 'Manager Category'];
        $response = $this->postJson(route('complaint-categories.store'), $data);
        $response->assertStatus(403);
    }

     public function test_reporter_cannot_create_category(): void
     {
         Sanctum::actingAs($this->reporter);
         $data = ['name' => 'Reporter Category'];
         $response = $this->postJson(route('complaint-categories.store'), $data);
         $response->assertStatus(403);
     }

     public function test_create_category_fails_with_validation_error(): void
     {
         Sanctum::actingAs($this->admin);
         ComplaintCategory::factory()->create(['name' => 'Existing Name']);

         // Missing name
         $response1 = $this->postJson(route('complaint-categories.store'), ['description' => 'desc only']);
         $response1->assertStatus(422)->assertJsonValidationErrors(['name']);

         // Duplicate name
         $response2 = $this->postJson(route('complaint-categories.store'), ['name' => 'Existing Name']);
         $response2->assertStatus(422)->assertJsonValidationErrors(['name']);
     }

    // --- SHOW ---
    public function test_admin_can_view_category(): void
    {
        $category = ComplaintCategory::factory()->create();
        Sanctum::actingAs($this->admin);

        $response = $this->getJson(route('complaint-categories.show', $category));

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $category->id]);
    }

     public function test_manager_can_view_category(): void
     {
         $category = ComplaintCategory::factory()->create();
         Sanctum::actingAs($this->manager);
         $response = $this->getJson(route('complaint-categories.show', $category));
         $response->assertStatus(200);
     }

    public function test_reporter_cannot_view_category(): void
    {
        $category = ComplaintCategory::factory()->create();
        Sanctum::actingAs($this->reporter);
        $response = $this->getJson(route('complaint-categories.show', $category));
        $response->assertStatus(403);
    }

     public function test_show_category_returns_404_if_not_found(): void
     {
         Sanctum::actingAs($this->admin);
         $response = $this->getJson(route('complaint-categories.show', 'invalid-uuid'));
         $response->assertStatus(404);
     }

    // --- UPDATE ---
    public function test_admin_can_update_category(): void
    {
        $category = ComplaintCategory::factory()->create(['name' => 'Old Name']);
        Sanctum::actingAs($this->admin);
        $data = ['name' => 'Updated Name', 'description' => 'New Desc'];

        $response = $this->putJson(route('complaint-categories.update', $category), $data);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Name', 'description' => 'New Desc']);
        $this->assertDatabaseHas('complaint_categories', ['id' => $category->id, 'name' => 'Updated Name']);
    }

     public function test_manager_cannot_update_category(): void
     {
         $category = ComplaintCategory::factory()->create();
         Sanctum::actingAs($this->manager);
         $response = $this->putJson(route('complaint-categories.update', $category), ['name' => 'Update Attempt']);
         $response->assertStatus(403);
     }

      public function test_reporter_cannot_update_category(): void
      {
          $category = ComplaintCategory::factory()->create();
          Sanctum::actingAs($this->reporter);
          $response = $this->putJson(route('complaint-categories.update', $category), ['name' => 'Update Attempt']);
          $response->assertStatus(403);
      }

       public function test_update_category_fails_with_validation_error(): void
       {
           $cat1 = ComplaintCategory::factory()->create(['name' => 'Cat 1']);
           $cat2 = ComplaintCategory::factory()->create(['name' => 'Cat 2']);
           Sanctum::actingAs($this->admin);

           // Try updating cat2 name to cat1's name (duplicate)
           $response = $this->putJson(route('complaint-categories.update', $cat2), ['name' => 'Cat 1']);
           $response->assertStatus(422)->assertJsonValidationErrors(['name']);
       }

        public function test_update_category_returns_404_if_not_found(): void
        {
            Sanctum::actingAs($this->admin);
            $response = $this->putJson(route('complaint-categories.update', 'invalid-uuid'), ['name' => 'Update']);
            $response->assertStatus(404);
        }


    // --- DELETE ---
    public function test_admin_can_delete_category(): void
    {
        $category = ComplaintCategory::factory()->create();
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson(route('complaint-categories.destroy', $category));

        $response->assertStatus(204); // No Content
        $this->assertDatabaseMissing('complaint_categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_category_if_in_use(): void
    {
        $category = ComplaintCategory::factory()->create();
        Complaint::factory()->create(['category_id' => $category->id]); // Link to a complaint
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson(route('complaint-categories.destroy', $category));

        $response->assertStatus(409); // Conflict
        $this->assertDatabaseHas('complaint_categories', ['id' => $category->id]); // Should still exist
    }

    public function test_manager_cannot_delete_category(): void
    {
        $category = ComplaintCategory::factory()->create();
        Sanctum::actingAs($this->manager);
        $response = $this->deleteJson(route('complaint-categories.destroy', $category));
        $response->assertStatus(403);
    }

    public function test_reporter_cannot_delete_category(): void
    {
        $category = ComplaintCategory::factory()->create();
        Sanctum::actingAs($this->reporter);
        $response = $this->deleteJson(route('complaint-categories.destroy', $category));
        $response->assertStatus(403);
    }

     public function test_delete_category_returns_404_if_not_found(): void
     {
         Sanctum::actingAs($this->admin);
         $response = $this->deleteJson(route('complaint-categories.destroy', 'invalid-uuid'));
         $response->assertStatus(404);
     }
}