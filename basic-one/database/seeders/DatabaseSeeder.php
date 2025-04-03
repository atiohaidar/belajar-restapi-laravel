<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $user1 = User::factory()->create(['email' => 'admin@example.com']);
        $user2 = User::factory()->create(['email' => 'user@example.com']);

        Post::factory(3)->create(['user_id' => $user1->id]);
        Post::factory(3)->create(['user_id' => $user2->id]);
    }
}

