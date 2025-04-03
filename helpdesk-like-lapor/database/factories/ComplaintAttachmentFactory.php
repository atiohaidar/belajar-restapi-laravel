<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str; // Import Str

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComplaintAttachment>
 */
class ComplaintAttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ComplaintAttachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = Str::random(10) . '_' . $this->faker->word . '.' . $this->faker->randomElement(['jpg', 'png', 'pdf', 'docx']);
        return [
            'complaint_id' => Complaint::factory(), // Creates a complaint if not provided
            'file_path' => '/uploads/complaints/' . $fileName, // Example path
            'file_name' => $fileName,
            'mime_type' => $this->faker->mimeType(),
            'uploaded_at' => now(),
        ];
    }
}