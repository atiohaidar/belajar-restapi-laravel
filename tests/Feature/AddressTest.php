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
        $user = User::where("id",1)->first();
        $contact = Contact::where("user_id",$user->id)->first();
        $response = $this->post('/api/contacts/'.$contact->id.'/addresses',[
            'street' => 'test',
            'city' => 'test',
            'postal_code' => 'test',
            'country' => 'test',
        ], [
            "Authorization"=> $user->token ,
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
        $user = User::where("id",1)->first();
        $contact = Contact::where("user_id",$user->id)->first();
        $response = $this->post('/api/contacts/'.($contact->id + 1).'/addresses',[
            'street' => 'test',
            'city' => 'test',
            'postal_code' => 'test',
            'country' => 'test',
        ], [
            "Authorization"=> $user->token ,
        ]);
        $response->assertJson([
            "errors"=>[
                "message"=> ["contact not found"],
            ]
            ]);
        $response->assertStatus(404);

    }
    public function testCreateAddressFailedContactNotBelongUser(): void
    {

        $this->seed([
            UserSeeder::class,
            ContactSeeder::class, SeederForPagination::class
        ]);
        $user = User::where("id",1)->first();
        $contact = Contact::where("user_id",$user->id + 1)->first();
        $response = $this->post('/api/contacts/'.($contact->id).'/addresses',[
            'street' => 'test',
            'city' => 'test',
            'postal_code' => 'test',
            'country' => 'test',
        ], [
            "Authorization"=> $user->token ,
        ]);
        $response->assertJson([
            "errors"=>[
                "message"=> ["contact not found"],
            ]
            ]);
        $response->assertStatus(404);
    }
    public function testGetAddressSuccess(): void{
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id",$address["data"]["id"])->first();
        $response = $this->get('/api/contacts/'.$address->contact_id.'/addresses/'. $address->id,[
            "Authorization" => User::where("id",1)->first()->token
        ]);
        $response->assertJson([
            "data"=>[
                "id"=> $address->id,
                "street"=> "test",
                "city"=> "test",
                "postal_code"=> "test",
                "country"=> "test",
            ]
        ]);
        $response->assertStatus(200);

    }
    public function testGetAddressNotFound(): void{
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id",$address["data"]["id"])->first();
        $response = $this->get('/api/contacts/'.$address->contact_id.'/addresses/'. ($address->id - 1),[
            "Authorization" => User::where("id",1)->first()->token
        ]);
        $response->assertJson([
           "errors" =>[
               "message"=> ["address not found"]
           ]
        ]);
           

        $response->assertStatus(404);

    }
    public function testGetAddressContactNotFound(): void{
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id",$address["data"]["id"])->first();
        $response = $this->get('/api/contacts/'.($address->contact_id + -1).'/addresses/'. ($address->id),[
            "Authorization" => User::where("id",1)->first()->token
        ]);
        $response->assertJson([
           "errors" =>[
               "message"=> ["contact not found"]
           ]
        ]);
           

        $response->assertStatus(404);

    }
    public function testGetAddressUserNotAuthorized(): void{
        $address = $this->testCreateAddressSuccess();
        $address = Address::where("id",$address["data"]["id"])->first();
        $response = $this->get('/api/contacts/'.($address->contact_id ).'/addresses/'. ($address->id),[
            "Authorization" => "nothing"
        ]);
        $response->assertJson([
           "errors" =>[
            "message"=> "Unauthorized"           ]
        ]);
           

        $response->assertStatus(401);

    }


}
