<?php
namespace Database\Factories;
use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;
class AgencyFactory extends Factory {
    protected $model = Agency::class;
    public function definition(): array {
        return [
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'parent_id' => null, // Default to no parent
        ];
    }
}