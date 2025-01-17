<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    //  biar datanya engga ngarih makanya make refresh database

    use RefreshDatabase;

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
    public function testRegisterFailedUserHasExist()
    {
        $this->testRegisterSuccess();
        $response = $this->post("/api/register", [
            "username" => "test",

            "password" => "test",
            "name" => "test name"
        ]);
        $response->assertStatus(400);
        $response->assertJson([
            "errors" => [
                "username" => [
                    "Username has exist"
                ]
            ]
        ]);
    }
    public function testLoginSuccess()
    {
        $this->testRegisterSuccess();

        $response = $this->post("/api/login", [
            "username" => "test",
            "password" => "test",
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            "data" => [
                "username" => "test",
                "name"=> "test name"
            ]
        ]);
    }
    public function testLoginFailWrongPassword()
    {
        $this->testRegisterSuccess();

        $response = $this->post("/api/login", [
            "username" => "test",
            "password" => "test2",
        ]); 
        $response->assertStatus(401);
        $response->assertJson([
            "errors" => [
                "message" => [
                    "username or password wrong"
                ]
            ]
        ]);
    }

}
