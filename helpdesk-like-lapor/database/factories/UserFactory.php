<?php
namespace Database\Factories;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class UserFactory extends Factory {
    protected $model = User::class;
    protected static ?string $password;
    public function definition(): array {
        return [
            'name' => $this->faker->name(),
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => $this->faker->phoneNumber,
            'role' => $this->faker->randomElement(['Admin', 'Agency Manager', 'Reporter']),
            'agency_id' => null, // Default, can be overridden
            'remember_token' => Str::random(10),
        ];
    }
    // Add state for specific roles or agency association if needed
    public function agencyManager(Agency $agency): static {
         return $this->state(fn (array $attributes) => [
             'role' => 'Agency Manager',
             'agency_id' => $agency->id,
         ]);
     }
     public function reporter(): static {
         return $this->state(fn (array $attributes) => [
             'role' => 'Reporter',
             'agency_id' => null, // Reporters might not have agency initially
         ]);
     }
}