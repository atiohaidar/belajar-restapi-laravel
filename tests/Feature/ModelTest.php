<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Contact;
use App\Models\User;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ModelTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function testUserModelCreate(): void
    {
        $user = User::create([
            "name" => "Tio Haidar Hanif",
            "username" => "atiohaidar",
            "password" => Hash::make("password"),
        ]);
        print (json_encode($user, JSON_PRETTY_PRINT));
        $this->assertTrue(true);
    }
    public function testContactModelCreate(): void
    {
        $data = [
            "user_id" => "1",
            "firstname" => "Akun lain",

        ];

        $contact = new Contact($data);
        $contact->user_id = "1";
        $contact->save();
        $this->assertTrue(true);
    }
    public function testContactModelGet(): void
    {
        $contact = Contact::with("user")->get();
        print (json_encode($contact, JSON_PRETTY_PRINT));
    }
    public function testAddressModelCreate(): void
    {
        $data = [
            'street' => "Jalan Balaikambang",
            'city' => "Purwokerto",
            'country' => "Indonesia",
            'province' => "Jawa Tengah",
            'postal_code' => "1233"
        ];
        $address = new Address($data);
        $address->contact_id= "1";
        $address->save();
        $this->assertTrue(true);
    }
    public function testAddressModelGet(): void
    {
        $address = Address::with("contact.user")->get();
        print (json_encode($address, JSON_PRETTY_PRINT));
    }
    public function testUserModelGet(): void{
        $user = User::where("id", "is", "")->get();   
        print (json_encode($user, JSON_PRETTY_PRINT));

    }
}
