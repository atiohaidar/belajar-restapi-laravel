<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'last_login' => $this->last_login,
            'last_active' => $this->last_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Include agency data if the relationship is loaded
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            // 'agency_id' => $this->agency_id, // Optionally include just the ID
        ];
        // NEVER INCLUDE 'password' or 'remember_token' here
    }
}