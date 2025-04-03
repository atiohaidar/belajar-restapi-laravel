<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }
    public function test_user_can_view_all_users()
    {
        $user = User::factory()->create();
        // get role admin
        $role = Role::where('name', 'user')->first();
        $user->roles()->attach($role);

        // Authenticate the user
        $role = Role::where('name', 'admin')->first();

        $authUser = User::factory()->create();
        $authUser->roles()->attach($role);
        $token = $authUser->createToken('auth_token')->plainTextToken;
        $response = $this->getJson('/api/users', [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'name', 'email', 'roles'],
        ]);      
    }
    
    public function test_user_can_be_assigned_a_role()
    {
        $user = User::factory()->create();

        $role = Role::where('name', 'admin')->first();

        // Authenticate the user and assign the "admin" role
        $authUser = User::factory()->create();
        $authUser->roles()->attach($role); // Assign the "admin" role to the authenticated user
        $token = $authUser->createToken('auth_token')->plainTextToken;

        $response = $this->postJson("/api/users/{$user->id}/assign-role", [
            'role' => 'admin',
        ], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Role assigned successfully']);
                 $this->assertTrue($authUser->hasRole('admin'));

        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_user_can_have_role_removed()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin')->first();
        $user->roles()->attach($role);
        
        // Authenticate the user
        $authUser = User::factory()->create();
        $authUser->roles()->attach($role);
        $token = $authUser->createToken('auth_token')->plainTextToken;

        $response = $this->postJson("/api/users/{$user->id}/remove-role", [
            'role' => 'admin',
        ], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Role removed successfully']);

        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_non_admin_user_cannot_access_protected_routes()
    {
        $user = User::factory()->create(); // Non-admin user
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->getJson('/api/users', [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Forbidden']);
    }

    public function test_user_can_only_see_their_own_details()
    {
        // Create a non-admin user
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Make a request to the endpoint
        $response = $this->getJson('/api/user-details', [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $user->id,
                     'name' => $user->name,
                     'email' => $user->email,
                 ]);
    }

    public function test_admin_can_see_all_users()
    {
        // Create an admin user
        $admin = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $admin->roles()->attach($adminRole);
        $token = $admin->createToken('auth_token')->plainTextToken;

        // Create additional users
        User::factory()->count(3)->create();

        // Make a request to the endpoint
        $response = $this->getJson('/api/user-details', [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'name', 'email', 'roles'],
                 ]);
    }
}
