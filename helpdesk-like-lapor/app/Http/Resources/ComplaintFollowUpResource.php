<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintFollowUpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'user' => [ // Basic info of user who performed follow-up
                'id' => $this->user->id,
                'name' => $this->user->name,
                'role' => $this->user->role,
            ],
            // Optionally include agency context if the agency_id was set and loaded
            // 'agency' => new AgencyResource($this->whenLoaded('agency')),
            'agency_id' => $this->agency_id, // Include agency id if set
        ];
    }
}