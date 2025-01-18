<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function testCreateContactSuccess(): void
    {

        $user = $this->seed(UserSeeder::class);

        $response = $this->post('/api/contacts', [
            'firstname' => 'test',
            'lastname' => 'test',
            'email' => 'test',
            'phone' => 'test',
        ], [
            "Authorization" => "test"
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            "data" => [
                "id" => "1",
                'firstname' => 'test',
                'lastname' => 'test',
                'email' => 'test',
                'phone' => 'test',
            ]
        ]);
    }
    public function testCreateContactFailedNotFoundUser(): void
    {
        $user = $this->seed(UserSeeder::class);

        $response = $this->post('/api/contacts', [
            'firstname' => 'test',
            'lastname' => 'test',
            'email' => 'test',
            'phone' => 'test',
        ], [
            "Authorization" => "tes"
        ]);

        $response->assertStatus(status: 401);
        $response->assertJson([
            "errors" => [
                "message" => "Unauthorized"
            ]
        ]);
    }
}
