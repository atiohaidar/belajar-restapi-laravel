<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\ContactSeeder;
use Database\Seeders\SeederForPagination;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function testCreateAddressSuccess()
    {

        $this->seed([
            UserSeeder::class,
            ContactSeeder::class
        ]);
        $user = User::where("id", 1)->first();
        $contact = Contact::where("user_id", $user->id)->first();
        $response = $this->post('/api/contacts/' . $contact->id . '/addresses', [
            'street' => 'test',
            'city' => 'test',
            'postal_code' => 'test',
            'country' => 'test',
        ], [
            "Authorization" => $user->token,
        ]);

        $response->assertStatus(201);
        return $response->json();
    }
    public function testCreateAddressFailedContactNotFound(): void
    {

        $this->seed([
            UserSeeder::class,
            ContactSeeder::class
        ]);
        $user = User::where("id", 1)->first();
        $contact = Contact::where("user_id", $user->id)->first();
        $response = $this->post('/api/contacts/' . ($contact->id + 1) . '/addresses', [
            'street' => 'test',
            'city' => 'test',
            'postal_code' => 'test',
            'country' => 'test',
        ], [
            "Authorization" => $user->token,
        ]);
        $response->assertJson([
            "errors" => [
                "message" => ["contact not found"],
            ]
        ]);
        $response->assertStatus(404);

    }
    public function testCreateAddressFailedContactNotBelongUser(): void
    {

        $this->seed([
            UserSeeder::class,
            ContactSeeder::class,
            SeederForPagination::class
        ]);
        $user = User::where("id", 1)->first();
        $contact = Contact::where("user_id", $user->id + 1)->first();
        $response = $this->post('/api/contacts/' . ($contact->id) . '/addresses', [
            'street' => 'test',
            'city' => 'test',
            'postal_code' => 'test',
            'country' => 'test',
        ], [
            "Authorization" => $user->token,
        ]);
        $response->assertJson([
            "errors" => [
                "message" => ["contact not found"],
            ]
        ]);
        $response->assertStatus(404);
    }
    public function testGetAddressSuccess(): void
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->get('/api/contacts/' . $address->contact_id . '/addresses/' . $address->id, [
            "Authorization" => User::where("id", 1)->first()->token
        ]);
        $response->assertJson([
            "data" => [
                "id" => $address->id,
                "street" => "test",
                "city" => "test",
                "postal_code" => "test",
                "country" => "test",
            ]
        ]);
        $response->assertStatus(200);

    }
    public function testGetAddressNotFound(): void
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->get('/api/contacts/' . $address->contact_id . '/addresses/' . ($address->id - 1), [
            "Authorization" => User::where("id", 1)->first()->token
        ]);
        $response->assertJson([
            "errors" => [
                "message" => ["address not found"]
            ]
        ]);


        $response->assertStatus(404);

    }
    public function testGetAddressContactNotFound(): void
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->get('/api/contacts/' . ($address->contact_id + -1) . '/addresses/' . ($address->id), [
            "Authorization" => User::where("id", 1)->first()->token
        ]);
        $response->assertJson([
            "errors" => [
                "message" => ["contact not found"]
            ]
        ]);


        $response->assertStatus(404);

    }
    public function testGetAddressUserNotAuthorized(): void
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->get('/api/contacts/' . ($address->contact_id) . '/addresses/' . ($address->id), [
            "Authorization" => "nothing"
        ]);
        $response->assertJson([
            "errors" => [
                "message" => "Unauthorized"
            ]
        ]);


        $response->assertStatus(401);

    }
  
    public function testListAddressSuccess()
    {
        $this->seed([
            SeederForPagination::class
        ]);
        $user = User::where("id", 1)->first();
        $contact = Contact::where("user_id", $user->id)->first();
        $response = $this->get('/api/contacts/' . ($contact->id) . '/addresses', [
            "Authorization" => User::where("id", 1)->first()->token
        ]);
        $response->assertStatus(200);

        print (json_encode($response->json(), JSON_PRETTY_PRINT));
        $response->assertJson(
            [
                "data" => array(
                    array(
                        "id" => 1,
                        "street" => "Jalan1",
                        "city" => "Kota1",
                        "province" => "Propinsi1",
                        "country" => "Negara1",
                        "postal_code" => "1231"
                    )
                )
            ]

        );


        print (json_encode(Address::where("id", 1)->with("contact.user")->get(), JSON_PRETTY_PRINT));
    }
    public function testListAddresses_WithMissingContact_ShoundreplyError()
    {
        $this->seed([
            SeederForPagination::class
        ]);
        $user = User::where("id", 1)->first();
        $response = $this->get('/api/contacts/100/addresses', [
            "Authorization" => $user->token
        ]);
        $response->assertStatus(404);

        print (json_encode($response->json(), JSON_PRETTY_PRINT));
        $response->assertJson(
            [
                "errors" => [
                    "message" => ["contact not found"]
                ]
            ]

        );
    }
    public function testUpdateAddress_WithValidData_ShouldReturnSuccess()
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->put('/api/contacts/' . $address->contact_id . '/addresses/' . $address->id, [
            'street' => 'test',
            'city' => 'wiw',
            'postal_code' => 'test',
            'country' => 'test',
        ], [
            "Authorization" => User::where("id", 1)->first()->token
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            "data" => [
                "id" => $address->id,
                "street" => "test",
                "city" => "wiw",
                "postal_code" => "test",
                "country" => "test",
            ]
        ]);
    }
    public function testUpdateAddress_WithInvalidDataAddress_ShouldReturnError()
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->put('/api/contacts/' . $address->contact_id . '/addresses/' . $address->id, [

        ], [
            "Authorization" => User::where("id", 1)->first()->token
        ]);
        $response->assertJson([
            "errors" => [
                "country" => ["The country field is required."],
            ]
        ]);
        $response->assertStatus(400);
    }
    public function testUpdateAddress_WithInvalidDataContact_ShouldReturnError()
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->put('/api/contacts/' . ($address->contact_id + 1) . '/addresses/' . $address->id, [
            'street' => 'test',
            'city' => 'wiw',
            'postal_code' => 'test',
            'country' => 'test',
        ], [
            "Authorization" => User::where("id", 1)->first()->token
        ]);
        $response->assertJson([
            "errors" => [
                "message" => ["contact not found"],
            ]
        ]);

        $response->assertStatus(404);
    }
    public function testDeleteAddress_WithValidDataAddress_Success()
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->delete(
            '/api/contacts/' . ($address->contact_id) . '/addresses/' . $address->id
            ,
            [],
            [
                "Authorization" => User::where("id", 1)->first()->token,
            ]
        );
        $response->assertJson([
            "data" => true
        ]);
        $response->assertStatus(200);
    }
    public function testDeleteAddress_WithInvalidDataAddress_ShouldReplyError()
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->delete(
            '/api/contacts/' . ($address->contact_id) . '/addresses/' . ($address->id + 1)
            ,
            [],
            [
                "Authorization" => User::where("id", 1)->first()->token,
            ]
        );
        $response->assertJson([
            "errors" => [
                "message" => ["address not found"]
            ]
        ]);
        $response->assertStatus(404);
    }
    public function testGetAddress_WithValidDataUser_ShouldReplySuccess()
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->get(
            '/api/contacts/' . ($address->contact_id) . '/addresses/' . $address->id
            ,
            [
                "Authorization" => User::where("id", 1)->first()->token,
            ]
        );
        $response->assertJson([
            "data" => [
                "id"=> 1,
                "street"=> "test",
                "city"=> "test",
                "province"=> null,
                "country"=> "test",
                "postal_code"=> "test"
            ]
        ]);
        $response->assertStatus(200);

    }
    public function testGetAddress_WithUnauthenticatedUser_ShouldReplyError()
    {
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id", $address["data"]["id"])->first();
        $response = $this->get(
            '/api/contacts/' . ($address->contact_id) . '/addresses/' . $address->id
            ,
            [
                "Authorization" => "nothing",
            ]
        );
        $response->assertJson([
            "errors" => [
                "message" => "Unauthorized"
            ]
        ]);
        $response->assertStatus(401);

    }
}

