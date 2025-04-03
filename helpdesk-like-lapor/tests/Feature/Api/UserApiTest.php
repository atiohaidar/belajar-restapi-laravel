<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $reporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->manager = User::factory()->create(['role' => 'Agency Manager', 'agency_id' => Agency::factory()->create()->id]);
        $this->reporter = User::factory()->create(['role' => 'Reporter']);
    }

    // --- INDEX ---
    public function test_admin_can_list_users_with_pagination_and_filter(): void
    {
        User::factory()->count(20)->create(['role' => 'Reporter']);
        User::factory()->count(5)->create(['role' => 'Agency Manager', 'agency_id' => Agency::factory()->create()->id]);
        Sanctum::actingAs($this->admin);

        // Basic list
        $res1 = $this->getJson(route('users.index'));
        $res1->assertStatus(200)
             ->assertJsonStructure(['data', 'links', 'meta'])
             ->assertJsonCount(15, 'data') // Default per_page
             ->assertJsonPath('meta.total', 28); // 20+5 + setUp users

        // Filter by role
        $res2 = $this->getJson(route('users.index', ['role' => 'Reporter']));
        $res2->assertStatus(200);
        // Check if all users in 'data' have role 'Reporter'
        foreach($res2->json('data') as $user) {
            $this->assertEquals('Reporter', $user['role']);
        }
        // This count depends on pagination, better to check total filtered count if possible or just check roles
         $this->assertGreaterThanOrEqual(21, $res2->json('meta.total')); // 20 + reporter from setup

         // Filter by search term
         $searchTerm = $this->reporter->name;
         $res3 = $this->getJson(route('users.index', ['search' => substr($searchTerm, 0, 5)])); // Partial search
         $res3->assertStatus(200)->assertJsonFragment(['id' => $this->reporter->id]);

        // Change pagination
         $res4 = $this->getJson(route('users.index', ['per_page' => 5]));
         $res4->assertStatus(200)->assertJsonCount(5, 'data');
    }

    public function test_non_admin_cannot_list_users(): void
    {
        foreach ([$this->manager, $this->reporter] as $user) {
            Sanctum::actingAs($user);
            $response = $this->getJson(route('users.index'));
            $response->assertStatus(403);
        }
    }

    // --- STORE ---
    public function test_admin_can_create_user(): void
    {
        Sanctum::actingAs($this->admin);
        $agency = Agency::factory()->create();
        $userData = [
            'name' => 'New Manager',
            'username' => 'newmanager',
            'email' => 'manager@new.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Agency Manager',
            'agency_id' => $agency->id,
        ];

        $response = $this->postJson(route('users.store'), $userData);

        $response->assertStatus(201)
                 ->assertJsonFragment(['username' => 'newmanager', 'role' => 'Agency Manager'])
                 ->assertJsonPath('data.agency.id', $agency->id);

        $this->assertDatabaseHas('users', ['username' => 'newmanager', 'agency_id' => $agency->id]);
        // Check password hash
        $createdUser = User::where('username', 'newmanager')->first();
        $this->assertTrue(Hash::check('password123', $createdUser->password));
    }

    public function test_non_admin_cannot_create_user(): void
    {
         $userData = ['name' => 'Test', 'username' => 'test', 'email' => 't@t.com', 'password' => 'p', 'password_confirmation' => 'p', 'role' => 'Reporter'];
         foreach ([$this->manager, $this->reporter] as $user) {
             Sanctum::actingAs($user);
             $response = $this->postJson(route('users.store'), $userData);
             $response->assertStatus(403);
         }
    }

    public function test_create_user_validation(): void
    {
        Sanctum::actingAs($this->admin);
        $agency = Agency::factory()->create();

        // Missing required fields
        $res1 = $this->postJson(route('users.store'), ['name' => 'Test']);
        $res1->assertStatus(422)->assertJsonValidationErrors(['username', 'email', 'password', 'role']);

        // Unique username/email
        $res2 = $this->postJson(route('users.store'), [
            'name' => 'Duplicate', 'username' => $this->admin->username, 'email' => 'd@d.com',
            'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'Reporter'
        ]);
        $res2->assertStatus(422)->assertJsonValidationErrors(['username']);

        // Agency required for manager
        $res3 = $this->postJson(route('users.store'), [
             'name' => 'Manager No Agency', 'username' => 'mgrnoagency', 'email' => 'm@na.com',
             'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'Agency Manager'
             // Missing agency_id
        ]);
        $res3->assertStatus(422)->assertJsonValidationErrors(['agency_id']);

        // Agency not required for Reporter/Admin
        $res4 = $this->postJson(route('users.store'), [
             'name' => 'Reporter No Agency', 'username' => 'rptrnoagency', 'email' => 'r@na.com',
             'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'Reporter'
             // Missing agency_id is OK
        ]);
        $res4->assertStatus(201); // Should succeed
    }

    // --- SHOW ---
    public function test_admin_can_view_user(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->getJson(route('users.show', $this->reporter));
        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $this->reporter->id])
                 ->assertJsonMissing(['password']); // Ensure password not shown
    }

    public function test_non_admin_cannot_view_user(): void
    {
        foreach ([$this->manager, $this->reporter] as $user) {
            Sanctum::actingAs($user);
            // Cannot view another user
            $response = $this->getJson(route('users.show', $this->admin));
            $response->assertStatus(403);
            // Cannot view self via this endpoint (per policy)
            $responseSelf = $this->getJson(route('users.show', $user));
             $responseSelf->assertStatus(403);
        }
    }

    // --- UPDATE ---
    public function test_admin_can_update_user(): void
    {
        Sanctum::actingAs($this->admin);
        $newAgency = Agency::factory()->create();
        $updateData = [
            'name' => 'Updated Name',
            'phone' => '123456789',
            'role' => 'Agency Manager',
            'agency_id' => $newAgency->id,
        ];

        $response = $this->putJson(route('users.update', $this->reporter), $updateData);
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Name', 'role' => 'Agency Manager'])
                 ->assertJsonPath('data.agency.id', $newAgency->id);

        $this->assertDatabaseHas('users', ['id' => $this->reporter->id, 'name' => 'Updated Name', 'agency_id' => $newAgency->id]);
    }

    public function test_admin_can_update_user_password(): void
    {
        Sanctum::actingAs($this->admin);
        $updateData = [
            'password' => 'newSecurePa$$',
            'password_confirmation' => 'newSecurePa$$',
        ];
        $response = $this->putJson(route('users.update', $this->reporter), $updateData);
        $response->assertStatus(200);
        $this->assertTrue(Hash::check('newSecurePa$$', $this->reporter->fresh()->password));
    }

    public function test_admin_can_change_user_role_and_nullify_agency(): void
    {
         Sanctum::actingAs($this->admin);
         $updateData = [
             'role' => 'Reporter', // Change from manager to reporter
             // No agency_id needed
         ];
          $response = $this->putJson(route('users.update', $this->manager), $updateData);
          $response->assertStatus(200)
                   ->assertJsonFragment(['role' => 'Reporter'])
                   ->assertJsonPath('data.agency', null); // Agency should be nullified
          $this->assertDatabaseHas('users', ['id' => $this->manager->id, 'role' => 'Reporter', 'agency_id' => null]);
    }

    public function test_non_admin_cannot_update_user(): void
    {
        $updateData = ['name' => 'Update Attempt'];
        foreach ([$this->manager, $this->reporter] as $user) {
            Sanctum::actingAs($user);
            // Cannot update another user
            $response = $this->putJson(route('users.update', $this->admin), $updateData);
            $response->assertStatus(403);
            // Cannot update self via this endpoint (per policy)
            $responseSelf = $this->putJson(route('users.update', $user), $updateData);
             $responseSelf->assertStatus(403);
        }
    }

    // Add more validation tests for update if needed (e.g., unique constraints)

    // --- DELETE ---
    public function test_admin_can_delete_user(): void
    {
        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson(route('users.destroy', $this->reporter));
        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $this->reporter->id]);
    }

     public function test_admin_cannot_delete_self(): void
     {
         Sanctum::actingAs($this->admin);
         $response = $this->deleteJson(route('users.destroy', $this->admin));
         $response->assertStatus(403); // Forbidden by policy
         $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
     }

    public function test_non_admin_cannot_delete_user(): void
    {
        foreach ([$this->manager, $this->reporter] as $user) {
            Sanctum::actingAs($user);
            // Cannot delete another user
            $response = $this->deleteJson(route('users.destroy', $this->admin));
            $response->assertStatus(403);
            // Cannot delete self
             $responseSelf = $this->deleteJson(route('users.destroy', $user));
             $responseSelf->assertStatus(403); // Also forbidden by policy
        }
    }

     // Test deletion constraints (e.g., complaints user_id set to null)
     public function test_deleting_user_sets_complaint_user_id_to_null(): void
     {
         Sanctum::actingAs($this->admin);
         $complaint = \App\Models\Complaint::factory()->create(['user_id' => $this->reporter->id]);
         $this->assertNotNull($complaint->user_id);

         $response = $this->deleteJson(route('users.destroy', $this->reporter));
         $response->assertStatus(204);

         $this->assertDatabaseMissing('users', ['id' => $this->reporter->id]);
         $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'user_id' => null]);
     }
}