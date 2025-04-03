<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration.
     *
     * @return void
     */
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'User registered successfully']);

        $this->assertDatabaseHas('users', [
            'email' => 'johndoe@example.com',
        ]);
    }

    /**
     * Test user login.
     *
     * @return void
     */
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'johndoe@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'token',
                     'user' => ['id', 'name', 'email'],
                 ]);
    }

    /**
     * Test user logout.
     *
     * @return void
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out successfully']);
    }

    /**
     * Test user cannot login with invalid credentials.
     *
     * @return void
     */
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'johndoe@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid credentials']);
                 
    }
    /**
     * Test user cannot register with existing email.
     *
     * @return void
     */
    public function test_user_cannot_register_with_existing_email()
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
        $response->assertJson([
                    'message'=> 'The email has already been taken.',
                    'errors' => [
                        'email' => ['The email has already been taken.'],
                    ],
                ]);
    }
    public function test_user_cannot_register_with_empty_fields()
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => '',
            'password' => '',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }
    public function test_user_cannot_login_with_empty_fields()
    {
        $response = $this->postJson('/api/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
                 $response->assertJson([
                    'message'=> 'The email field is required. (and 1 more error)',
                    'errors' => [
                        'email' => ['The email field is required.'],
                        'password' => ['The password field is required.'],
                    ],
                ]);
            }
}
