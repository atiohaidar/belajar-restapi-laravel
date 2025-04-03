<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\ComplaintFollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComplaintFollowUp>
 */
class ComplaintFollowUpFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ComplaintFollowUp::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->uuid(),

            'complaint_id' => Complaint::factory(),
            'user_id' => User::factory(),
            // Creates an agency context by default, can be set to null when using factory
            'agency_id' => Agency::factory(),
            'description' => $this->faker->paragraph(3),
            // created_at/updated_at are handled automatically by timestamps()
        ];
    }

    /**
     * Indicate the follow-up has no specific agency context (user's agency might be implicit).
     */
    public function withoutAgencyContext(): static
    {
        return $this->state(fn (array $attributes) => [
            'agency_id' => null,
        ]);
    }
}