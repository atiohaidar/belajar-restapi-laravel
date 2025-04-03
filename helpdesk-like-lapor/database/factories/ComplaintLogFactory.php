<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComplaintLog>
 */
class ComplaintLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ComplaintLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['Created', 'Status Update', 'Assigned', 'Transferred', 'Comment Added', 'Attachment Added', 'Resolved'];
        $action = $this->faker->randomElement($actions);
        $details = null;

        if ($action === 'Status Update') {
            $details = 'Status changed to ' . $this->faker->randomElement(['Pending', 'In Progress', 'Resolved']);
        } elseif ($action === 'Assigned' || $action === 'Transferred') {
             $details = $action . ' to Agency: ' . $this->faker->company;
        }

        return [
            'id' => $this->faker->unique()->uuid(),

            'complaint_id' => Complaint::factory(),
            // Creates a user by default, can be set to null via state
            'user_id' => User::factory(),
            'action' => $action,
            'details' => $details,
            'timestamp' => $this->faker->dateTimeThisYear(), // Use a specific timestamp
        ];
    }

    /**
     * Indicate the action was performed by the system (no user).
     */
    public function systemAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }
}