<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    /**
     * Indicates if the resource's relationships should be loaded.
     * Useful for controlling default loading in index vs show.
     *
     * @var bool
     */
    public static $loadRelationships = true; // Default to load for 'show'

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Basic info for related models (always include)
            'reporter_id' => $this->user_id,
            'reporter_name' => $this->user?->name, // Handle potential null user
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name, // Handle potential null category
            'assigned_agency_id' => $this->agency_id,
            'assigned_agency_name' => $this->agency?->name, // Handle potential null agency

            // Conditionally load full relationships (typically for 'show' endpoint)
            $this->mergeWhen(static::$loadRelationships && $this->relationLoaded('user'), [
                'user' => new UserResource($this->user),
            ]),
            $this->mergeWhen(static::$loadRelationships && $this->relationLoaded('agency'), [
                'agency' => new AgencyResource($this->agency),
            ]),
             $this->mergeWhen(static::$loadRelationships && $this->relationLoaded('category'), [
                 'category' => new ComplaintCategoryResource($this->category),
             ]),
             $this->mergeWhen(static::$loadRelationships && $this->relationLoaded('attachments'), [
                 'attachments' => ComplaintAttachmentResource::collection($this->attachments),
             ]),
            $this->mergeWhen(static::$loadRelationships && $this->relationLoaded('comments'), [
                'comments' => CommentResource::collection($this->comments),
            ]),
             $this->mergeWhen(static::$loadRelationships && $this->relationLoaded('logs'), [
                 'logs' => ComplaintLogResource::collection($this->logs),
             ]),
            // Add followups, transfers, ratings when those resources/models exist and are loaded
        ];
    }

     /**
      * Create a new anonymous resource collection.
      * Disable relationship loading for collections by default.
      *
      * @param  mixed  $resource
      * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
      */
     public static function collection($resource)
     {
        // katanya untuk mempercepat proses aja
         static::$loadRelationships = false; // Don't load details in lists
         $collection = parent::collection($resource);
         static::$loadRelationships = true; // Reset for subsequent individual resources
         return $collection;
     }
}