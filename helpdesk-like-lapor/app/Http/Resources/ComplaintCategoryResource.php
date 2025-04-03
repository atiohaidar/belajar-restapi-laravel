<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request); // Default - includes all model attributes

        // Customize output
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            // Add counts or other derived data if needed
            // 'complaints_count' => $this->whenCounted('complaints'), // If you load count
        ];
    }
}