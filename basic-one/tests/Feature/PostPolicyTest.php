<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Membuat dua user
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();

        // Membuat post yang dimiliki oleh user1
        $this->post = Post::factory()->create(['user_id' => $this->user1->id]);
    }

    /** @test */
    public function user_can_update_own_post()
    {
        $response = $this->actingAs($this->user1)->putJson(route('posts.update', $this->post), [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_post()
    {
        $response = $this->actingAs($this->user2)->putJson(route('posts.update', $this->post), [
            'title' => 'Hacked Title',
            'content' => 'Hacked Content',
        ]);

        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function user_can_delete_own_post()
    {
        $response = $this->actingAs($this->user1)->deleteJson(route('posts.destroy', $this->post));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('posts', ['id' => $this->post->id]);
    }

    /** @test */
    public function user_cannot_delete_other_users_post()
    {
        $response = $this->actingAs($this->user2)->deleteJson(route('posts.destroy', $this->post));

        $response->assertStatus(403); // Forbidden
    }
}
