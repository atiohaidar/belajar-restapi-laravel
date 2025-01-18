<?php

namespace Tests\Feature;

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
    public function testCreateAddressSuccess(): void
    {

        $this->seed([
            UserSeeder::class,
            ContactSeeder::class
        ]);
        $user = User::where("id",1)->first();
        $contact = Contact::where("user_id",$user->id)->first();
        $response = $this->post('/api/contacts/'.$contact->id.'/addresses',[
            'address' => 'test',
            'city' => 'test',
            'postal_code' => 'test',
            'country' => 'test',
        ], [
            "Authorization"=> $user->token ,
        ]);

        $response->assertStatus(201);
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
            'address' => 'test',
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
            'address' => 'test',
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

}
