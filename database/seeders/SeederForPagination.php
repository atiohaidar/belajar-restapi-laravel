<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Hash;
class SeederForPagination extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                "username" => "test" . $i,
                "password" => Hash::make("test"),
                "name" => "test",
                "token" => "test" . $i
            ]);
        }
        for ($i = 1; $i <= 22; $i++) {
            $user = User::where("id", 1)->first();

            if ($i > 15) {
                $user = User::where("id", 2)->first();
            }

            Contact::create([
                "firstname" => "first" . $i,
                "lastname" => "last" . $i,
                "phone" => "11212" . $i,
                "email" => "test@gmail.com" . $i,
                "user_id" => $user->id,
            ]);
        }
        for ($i = 1; $i <= 10; $i++) {
            $contact = Contact::where("id", $i  )->first();
            // print($contact);
            Address::create([
                'street' => "Jalan" . $i,
                'city' => "Kota" . $i,
                'country' => "Negara" . $i,
                'province' => "Propinsi" . $i,
                'postal_code' => "123" . $i,
                'contact_id' => $contact->id,
            ]);
        }
    }
}
