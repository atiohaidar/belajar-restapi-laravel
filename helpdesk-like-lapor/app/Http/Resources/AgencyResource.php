<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyResource extends JsonResource
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
            'address' => $this->address,
            'email' => $this->email,
            'phone' => $this->phone,
            'parent_id' => $this->parent_id,
            // Optionally include parent/child data when loaded
            'parent_agency' => new AgencyResource($this->whenLoaded('parentAgency')),
            'child_agencies' => AgencyResource::collection($this->whenLoaded('childAgencies')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}