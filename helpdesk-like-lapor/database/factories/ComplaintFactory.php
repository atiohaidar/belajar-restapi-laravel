<?php
namespace Database\Factories;
use App\Models\Agency;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
class ComplaintFactory extends Factory {
    protected $model = Complaint::class;
    public function definition(): array {
        return [
            'id' => $this->faker->unique()->uuid(),

            // Ensure related models are created if not provided
            'user_id' => User::factory(),
            'agency_id' => Agency::factory(),
            'category_id' => ComplaintCategory::factory(),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'status' => 'Unprocessed', // Default status
            //  ini penyrebab kenapa di testcase test_users_can_add_comment_based_on_role_and_ownership kadang error dan kadang engga
            //  pentyebabnya karena random, dan akan eror kalo ternyatra datanya lagi diarsipin
            // 'status' => $this->faker->randomElement(['Unprocessed', 'Pending', 'In Progress', 'Resolved', 'Archived']),
            'priority' => $this->faker->randomElement(['Low', 'Medium', 'High']),
        ];
    }
     // State for unassigned complaint
     public function unassigned(): static {
         return $this->state(fn (array $attributes) => [
             'agency_id' => null,
             'status' => 'Unprocessed',
         ]);
     }
}