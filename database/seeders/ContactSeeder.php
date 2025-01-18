<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $username = "test";
        $user = User::where("username", $username)->first();
        Contact::create([
            'firstname' => 'test 1',
            'lastname' => 'test',
            'email' => 'test',
            'phone' => 'test',
            'user_id'=> $user->id,
        ]);
    }
}
