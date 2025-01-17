<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function testRegisterSuccess()
    {
        $response = $this->post("/api/register", [
            "username" => "test",
            "password" => "test",
            "name" => "test name"
        ]);
        $response->assertStatus(201);
        $response->assertJson([
            "data" => [
                "username" => "test",
                "name"=> "test name"
            ]
        ]);
    }
    public function testRegisterFailedInput()
    {
        $response = $this->post("/api/register", [
            "password" => "test",
            "name" => "test name"
        ]);
        $response->assertStatus(400);
        $response->assertJson([
            "errors" => [
                "username" => [
                    "The username field is required."
                ]
            ]
        ]);
    }
}
