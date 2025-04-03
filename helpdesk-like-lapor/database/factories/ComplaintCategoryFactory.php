<?php
namespace Database\Factories;
use App\Models\ComplaintCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
class ComplaintCategoryFactory extends Factory {
    protected $model = ComplaintCategory::class;
    public function definition(): array {
        return [
            'id' => $this->faker->unique()->uuid(),

            'name' => $this->faker->unique()->words(3, true), // Unique category name
            'description' => $this->faker->sentence,
        ];
    }
}