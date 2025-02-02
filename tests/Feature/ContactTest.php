<?php

namespace Tests\Feature;

use App\Models\Contact;
use Database\Seeders\SeederForPagination;
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
    public function testCreateContactSuccess()
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
        return $response->json();
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
    public function testUpdateContactSuccess(): void
    {
        $oldContact = $this->testCreateContactSuccess();
        $response = $this->put('/api/contacts/' . $oldContact["data"]["id"], [
            'firstname' => 'test',
            'lastname' => 'test',
            'email' => 'waduh',
            'phone' => 'wa',
        ], [
            "Authorization" => "test"
        ]);
        $response->assertStatus(status: 200);
        $response->assertJson([
            "data" => [
                "id" => "1",
                'firstname' => 'test',
                'lastname' => 'test',
                'email' => 'waduh',
                'phone' => 'wa',
            ]
        ]);
        self::assertNotEquals($oldContact["data"]["email"], $response->json()["data"]["email"]);

    }
    public function testUpdateContactErrorInput(): void
    {
        $oldContact = $this->testCreateContactSuccess();
        $response = $this->put('/api/contacts/' . $oldContact["data"]["id"], [

        ], [
            "Authorization" => "test"
        ]);
        $response->assertStatus(status: 400);
        $response->assertJson([
            "errors" => [
                "firstname" => ["The firstname field is required."]
            ]
        ]);
        self::assertEquals($oldContact["data"]["email"], Contact::find($oldContact["data"]["id"])->email);

    }
    public function testGetContactSuccess(): void
    {
        $oldContact = $this->testCreateContactSuccess();
        $response = $this->get(
            '/api/contacts/' . $oldContact["data"]["id"]
            ,
            [
                "Authorization" => "test"
            ]
        );
        print(json_encode($response->json(), JSON_PRETTY_PRINT));
        $response->assertStatus(status: 200);
        $response->assertJson([
            "data" => [
                "id" => "1",
                'firstname' => 'test',
                'lastname' => 'test',
                'email' => 'test',
                'phone' => 'test',
            ]
        ]);

        self::assertEquals($oldContact["data"]["email"], $response->json()["data"]["email"]);

    }
    public function testGetContactNotFound(): void
    {
        $oldContact = $this->testCreateContactSuccess();
        $response = $this->get(
            '/api/contacts/' . ($oldContact["data"]["id"] + 1)
            ,
            [
                "Authorization" => "test"
            ]
        );
        $response->assertStatus(status: 404);
        $response->assertJson([
            "errors" => [
                "message" => ["not found"]
            ]

        ]);

    }
    public function testDeleteContactSuccess(): void
    {
        $oldContact = $this->testCreateContactSuccess();
        $response = $this->delete(
            '/api/contacts/' . $oldContact["data"]["id"]
            ,
            []
            ,
            [
                "Authorization" => "test"
            ]
        );
        $response->assertStatus(status: 200);
        $response->assertJson([
            "data" => true
        ]);
        self::assertNull(Contact::find($oldContact["data"]["id"]));

    }
    public function testDeleteContactFailNotFound(): void{
        $oldContact = $this->testCreateContactSuccess();
        $response = $this->delete(
            "/api/contacts/". ($oldContact["data"]["id"] + 1),
            [],
            [
                "Authorization"=> "test"
                ]
        );
        
        $response->assertStatus(404);
    }
    public function testDeleteContactFailUnauthorized(): void{
        $oldContact = $this->testCreateContactSuccess();
        $response = $this->delete(
            "/api/contacts/". ($oldContact["data"]["id"] + 1),
            [],
            [
                "Authorization"=> "tes"
                ]
        );
        
        $response->assertJson([
            "errors"=>[
                "message"=>"Unauthorized"
            ]   
            ]);
        $response->assertStatus(401);
    }
    public function testListContactSuccess(): void{

        $this->seed(SeederForPagination::class);

        $response = $this->get(
            "/api/contacts?page=1&size=5",
            [
                "Authorization"=> "test1"
            ]
            );
            print(json_encode($response->json(), JSON_PRETTY_PRINT));
            self::assertEquals(15, $response->json()["meta"]["total"]);
            $response->assertStatus(200);
            


        
    }

    public function testListContactSuccessQuery(): void{

        $this->seed(SeederForPagination::class);

        $response = $this->get(
            "/api/contacts?name=2&email=test&phone=1",
            [
                "Authorization"=> "test1"
            ]
            );
            print(json_encode($response->json(), JSON_PRETTY_PRINT));
            self::assertEquals(2, $response->json()["meta"]["total"]);
            $response->assertStatus(200);
    }
    public function testListContactDontHaveContact(): void{
        $this->seed(SeederForPagination::class);
        $response = $this->get(
            "/api/contacts?name=2&email=3&phone=4",
            [
                "Authorization"=> "test1"
            ]
            );
            print(json_encode($response->json(), JSON_PRETTY_PRINT));
            self::assertEquals(0, $response->json()["meta"]["total"]);
            $response->assertStatus(200);

        }
        public function testListContacErrorUnauthorized(): void{
            $this->seed(SeederForPagination::class);
            $response = $this->get(
                "/api/contacts?name=2&email=3&phone=4",
                [
                    "Authorization"=> "tesx"
                ]
                );
                
                print(json_encode($response->json(), JSON_PRETTY_PRINT));
                $response->assertStatus(401);
            }
}
