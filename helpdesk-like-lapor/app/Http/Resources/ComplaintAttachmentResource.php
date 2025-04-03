<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage; // Import Storage

class ComplaintAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'url' => Storage::disk('public')->url($this->file_path), // Assuming 'public' disk and stored path relative to disk root
            // Consider signed URLs if using private storage (e.g., S3 private)
            'uploaded_at' => $this->uploaded_at,
        ];
    }
}