<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'details' => $this->details,
            'timestamp' => $this->timestamp,
             // Include basic user info if available
             'user' => $this->whenLoaded('user', fn() => [ // Use closure for conditional loading check
                 'id' => $this->user->id,
                 'name' => $this->user->name,
                 'role' => $this->user->role,
             ], null), // Return null if user not loaded or null
        ];
    }
}