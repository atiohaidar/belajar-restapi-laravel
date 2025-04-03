<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'agency_id',
        'complaint_id',
        'stars',
        'review',
    ];

     protected $casts = [
        'stars' => 'integer', // Or keep as default if no specific casting needed
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship: Rating belongs to a user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship: Rating belongs to an agency
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    // Relationship: Rating belongs to a complaint (optional)
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }
}