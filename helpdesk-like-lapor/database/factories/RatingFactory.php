<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rating>
 */
class RatingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Rating::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'agency_id' => Agency::factory(),
            // Creates a related complaint by default, can be set to null via state
            'complaint_id' => Complaint::factory(),
            'stars' => $this->faker->numberBetween(1, 5),
            'review' => $this->faker->optional(70)->paragraph(1), // 70% chance of having a review
             // created_at/updated_at are handled automatically by timestamps()
        ];
    }

    /**
     * Indicate the rating is general and not tied to a specific complaint.
     */
    public function generalRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'complaint_id' => null,
        ]);
    }
}