<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'agency_id',
        'category_id',
        'title',
        'description',
        'status',
        'priority',
        // created_at/updated_at handled by timestamps()
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship: Complaint belongs to a user (reporter)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship: Complaint belongs to an agency (assigned)
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    // Relationship: Complaint belongs to a category
    public function category(): BelongsTo
    {
        return $this->belongsTo(ComplaintCategory::class, 'category_id');
    }

    // Relationship: Complaint has many attachments
    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class, 'complaint_id');
    }

    // Relationship: Complaint can trigger many notifications
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'complaint_id');
    }

    // Relationship: Complaint has many follow-ups
    public function followUps(): HasMany
    {
        return $this->hasMany(ComplaintFollowUp::class, 'complaint_id');
    }

    // Relationship: Complaint has many transfers
    public function transfers(): HasMany
    {
        return $this->hasMany(ComplaintTransfer::class, 'complaint_id');
    }

    // Relationship: Complaint can be the basis for ratings
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'complaint_id');
    }

    // Relationship: Complaint has many log entries
    public function logs(): HasMany
    {
        return $this->hasMany(ComplaintLog::class, 'complaint_id');
    }

     // Relationship: Complaint has many comments
     public function comments(): HasMany
     {
         return $this->hasMany(Comment::class, 'complaint_id');
     }
}