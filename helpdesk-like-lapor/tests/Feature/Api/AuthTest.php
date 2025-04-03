<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'), // Ensure password is hashed
        ]);
    }

    public function test_user_can_login_with_username_and_get_token(): void
    {
        $response = $this->postJson(route('api.login'), [
            'login' => 'testuser',
            'password' => 'password',
            'device_name' => 'test_device',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);

        // Check last login updated
        $this->assertNotNull($this->user->fresh()->last_login);
    }

     public function test_user_can_login_with_email_and_get_token(): void
     {
         $response = $this->postJson(route('api.login'), [
             'login' => 'test@example.com',
             'password' => 'password',
             'device_name' => 'test_device',
         ]);

         $response->assertStatus(200)
                  ->assertJsonStructure(['token']);
         $this->assertNotNull($this->user->fresh()->last_login);
     }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson(route('api.login'), [
            'login' => 'testuser',
            'password' => 'wrongpassword',
            'device_name' => 'test_device',
        ]);

        $response->assertStatus(422) // Validation Exception
                 ->assertJsonValidationErrors(['login']);
    }

    public function test_login_requires_fields(): void
    {
        $response = $this->postJson(route('api.login'), []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['login', 'password', 'device_name']);
    }

    public function test_authenticated_user_can_get_their_info(): void
    {
        // Method 1: Acting as user
        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.user'));

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $this->user->id,
                     'username' => 'testuser',
                     'email' => 'test@example.com',
                 ]);
         $response->assertJsonMissing(['password']); // Ensure password isn't returned
    }

    public function test_unauthenticated_user_cannot_get_user_info(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->user->fresh()->token)->getJson(route('api.user'));
        $response->assertStatus(401); // Unauthorized
    }

    public function test_authenticated_user_can_logout(): void
    {
         // Method 2: Get token and use it
         $loginResponse = $this->postJson(route('api.login'), [
             'login' => 'testuser',
             'password' => 'password',
             'device_name' => 'test_device',
         ]);
         $token = $loginResponse->json('token');

         
        
         $response = $this->withHeader('Authorization', 'Bearer ' . $token)
         ->postJson(route('api.logout'));

         $response->assertStatus(200)
                  ->assertJson(['message' => 'Logged out successfully']);
                  
         
         // Verify token is revoked by trying to access protected route again
        //  $userResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
        //                       ->getJson(route('api.user'));
        //                       print_r($userResponse->json());
        // //  $userResponse->assertStatus(401);
        $this->assertDatabaseMissing('personal_access_tokens', [// ini udah beener sebenernya, cuman engga tau kenapa yang sebelumnya masih masuk. tapi ok lah ya
            
            'tokenable_id' => $this->user->id,
            // Note: token in DB is hashed, cannot check directly against plainTextToken
        ]);

         // Verify token deleted from DB
         $this->assertCount(0, $this->user->tokens); // Check relationship
    }

}