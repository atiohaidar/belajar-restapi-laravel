<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stars' => $this->stars,
            'review' => $this->review,
            'created_at' => $this->created_at,
            // Info about who gave the rating
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            // Info about the rated agency
            'agency' => [
                'id' => $this->agency?->id,
                'name' => $this->agency?->name,
            ],
            // Info about the related complaint (optional)
            'complaint' => $this->whenLoaded('complaint', fn() => [
                    'id' => $this->complaint->id,
                    'title' => $this->complaint->title,
                ], null),
            'complaint_id' => $this->complaint_id, // Always include the ID if present
        ];
    }
}