<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\ComplaintTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComplaintTransfer>
 */
class ComplaintTransferFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ComplaintTransfer::class;

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
            // Creates a 'from' agency by default, can be set to null via state
            'from_agency_id' => Agency::factory(),
            'to_agency_id' => Agency::factory(), // Needs a destination agency
            'user_id' => User::factory(), // User who initiated
            'reason' => $this->faker->optional()->sentence(),
            // created_at/updated_at are handled automatically by timestamps()
        ];
    }

    /**
     * Indicate the transfer was from an unassigned state.
     */
    public function fromUnassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_agency_id' => null,
        ]);
    }
}