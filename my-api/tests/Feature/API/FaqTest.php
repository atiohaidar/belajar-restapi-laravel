<?php

namespace Tests\Feature\API;

use Modules\Faq\app\Models\Faq;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;
    protected User $user;
    protected string $adminToken;
    protected string $userToken;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        // Create an admin user
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;
        // Create a regular user
        $this->user = User::factory()->create();
        $this->user->roles()->attach($userRole);
        $this->userToken = $this->user->createToken('user-token')->plainTextToken;
    }

    /**
     * Test getting all FAQs (public).
     */
    public function test_get_all_faqs_public(): void
    {
        // Create some published FAQs
        Faq::create([
            'question' => 'Test Question 1',
            'answer' => 'Test Answer 1',
            'is_published' => true,
            'order' => 1
        ]);

        Faq::create([
            'question' => 'Test Question 2',
            'answer' => 'Test Answer 2',
            'is_published' => true,
            'order' => 2
        ]);

        // Create an unpublished FAQ (should not be visible to public)
        Faq::create([
            'question' => 'Hidden Question',
            'answer' => 'Hidden Answer',
            'is_published' => false,
            'order' => 3
        ]);

        $response = $this->getJson('/api/faqs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'question',
                        'answer',
                        'is_published',
                        'order',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data'); // Only published FAQs
    }

    /**
     * Test getting all FAQs (admin sees all).
     */
    public function test_get_all_faqs_admin(): void
    {
        // Create some FAQs (both published and unpublished)
        Faq::create([
            'question' => 'Test Question 1',
            'answer' => 'Test Answer 1',
            'is_published' => true,
            'order' => 1
        ]);

        Faq::create([
            'question' => 'Hidden Question',
            'answer' => 'Hidden Answer',
            'is_published' => false,
            'order' => 2
        ]);


        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/faqs.search');
        print_r($response->getContent());

            // print_r($this->admin->roles()); // Check if the admin has the role
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Admin sees all FAQs
    }

    /**
     * Test creating a new FAQ (admin only).
     */
    public function test_admin_can_create_faq(): void
    {
        $faqData = [
            'question' => 'New FAQ Question',
            'answer' => 'New FAQ Answer',
            'is_published' => true,
            'category' => 'general'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/faqs', $faqData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'question',
                    'answer',
                    'is_published',
                    'category',
                    'order',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('faqs', [
            'question' => 'New FAQ Question'
        ]);
    }

    /**
     * Test that regular users cannot create FAQs.
     */
    public function test_user_cannot_create_faq(): void
    {
        $faqData = [
            'question' => 'New FAQ Question',
            'answer' => 'New FAQ Answer',
            'is_published' => true
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/faqs', $faqData);

        $response->assertStatus(403);
    }

    /**
     * Test viewing a specific FAQ.
     */
    public function test_view_specific_faq(): void
    {
        $faq = Faq::create([
            'question' => 'Test Question',
            'answer' => 'Test Answer',
            'is_published' => true,
            'order' => 1
        ]);

        $response = $this->getJson('/api/faqs/' . $faq->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'question',
                    'answer',
                    'is_published',
                    'order',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test updating an FAQ (admin only).
     */
    public function test_admin_can_update_faq(): void
    {
        $faq = Faq::create([
            'question' => 'Original Question',
            'answer' => 'Original Answer',
            'is_published' => true,
            'order' => 1
        ]);

        $updateData = [
            'question' => 'Updated Question',
            'answer' => 'Updated Answer',
            'is_published' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/faqs/' . $faq->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'FAQ updated successfully'
            ]);

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'Updated Question',
            'answer' => 'Updated Answer',
            'is_published' => 0
        ]);
    }

    /**
     * Test deleting an FAQ (admin only).
     */
    public function test_admin_can_delete_faq(): void
    {
        $faq = Faq::create([
            'question' => 'Delete Question',
            'answer' => 'Delete Answer',
            'is_published' => true,
            'order' => 1
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/faqs/' . $faq->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'FAQ deleted successfully'
            ]);

        $this->assertDatabaseMissing('faqs', [
            'id' => $faq->id
        ]);
    }
}
