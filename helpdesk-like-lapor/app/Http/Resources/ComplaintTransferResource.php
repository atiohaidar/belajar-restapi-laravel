<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
            // Info about the transfer initiator
            'user' => [
                'id' => $this->user?->id, // Handle if user deleted (set null)
                'name' => $this->user?->name,
            ],
            // Info about agencies involved
            'from_agency' => [
                'id' => $this->from_agency_id,
                'name' => $this->fromAgency?->name, // Load relationship 'fromAgency' if needed
            ],
             'to_agency' => [
                 'id' => $this->to_agency_id,
                 'name' => $this->toAgency?->name, // Load relationship 'toAgency' if needed
             ],
            // Include full AgencyResource if needed and loaded
            // 'from_agency_full' => new AgencyResource($this->whenLoaded('fromAgency')),
            // 'to_agency_full' => new AgencyResource($this->whenLoaded('toAgency')),
        ];
    }
}