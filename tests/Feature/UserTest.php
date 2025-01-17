<?php

namespace Tests\Feature;

use App\Models\User;
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
                "name" => "test name"
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
        $user = User::where("username", "test")->first();
        $response->assertStatus(200);
        $response->assertJson([
            "data" => [
                "username" => "test",
                "name" => "test name",
                "token" => $user->token
            ]
        ]);
        return $user->token;
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
    public function testGetCurrentUserLogin()
    {
        $token = $this->testLoginSuccess();

        $response = $this->get("/api/users/current", [
            "Authorization" => $token,
        ]);
        // print_r( $response->json());
        $response->assertStatus(200);
        $response->assertJson([
            "data" => [
                "username" => "test",
                "name" => "test name",
                "token" => $token
            ]
        ]);

    }
    public function testGetCurrentUserLoginFailureUnauthorized()
    {
        $token = $this->testLoginSuccess();
        $response = $this->get("/api/users/current", [
            "Authorization" => $token . "wa",
        ]);
        // print_r( $response->json());
        $response->assertStatus(401);
        $response->assertJson([
            "errors" => [
                "message" =>
                    "Unauthorized"
            ]
        ]);
    }
    public function testUpdateUserSuccess()
    {
        $token = $this->testLoginSuccess();
        $response = $this->patch("/api/users/current", [
            "password" => "test",
            "name" => "test name baru"
        ],[
            "Authorization" => $token,
        ]);
        // print_r( $response->json());
        $response->assertStatus(200);
        $response->assertJson(
            [
                "data" => [
                    "username" => "test",
                    "name" => "test name baru",
                    "token" => $token
                ]
            ]
        );
        $user = User::where("token", $token)->first();
        

    }

    public function testLogoutSuccess()
    {
        $token = $this->testLoginSuccess();
        // print_r($token);
        $response = $this->delete("/api/users/logout", [], ["Authorization" => $token]);
        $response->assertStatus(200);
        $response->assertJson([
            "data" => true
        ]);
    }
}