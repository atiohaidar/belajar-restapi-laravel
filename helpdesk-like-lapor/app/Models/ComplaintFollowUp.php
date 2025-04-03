<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintFollowUp extends Model
{
    use HasFactory, HasUuids;

    // Explicitly define table name if it differs from plural snake case convention
    // protected $table = 'complaint_follow_ups';

    protected $fillable = [
        'complaint_id',
        'user_id',
        'agency_id',
        'description',
    ];

     protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship: Follow-up belongs to a complaint
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    // Relationship: Follow-up belongs to a user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship: Follow-up belongs to an agency (optional context)
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }
}