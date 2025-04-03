<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Creates a user if not provided
            // Creates a complaint by default, can be set to null when using the factory
            'complaint_id' => Complaint::factory(),
            'message' => $this->faker->sentence(10),
            'is_read' => false, // Default based on migration
            // created_at/updated_at are handled automatically by timestamps()
        ];
    }

    /**
     * Indicate that the notification is not linked to a specific complaint.
     */
    public function systemLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'complaint_id' => null,
        ]);
    }

    /**
     * Indicate that the notification has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }
}