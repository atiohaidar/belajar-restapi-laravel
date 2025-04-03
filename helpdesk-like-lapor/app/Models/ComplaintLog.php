<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintLog extends Model
{
    use HasFactory, HasUuids;

    // Disable standard timestamps if 'timestamp' field is used instead
    public $timestamps = false;

    protected $fillable = [
        'complaint_id',
        'user_id',
        'action',
        'details',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    // Relationship: Log belongs to a complaint
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    // Relationship: Log belongs to a user (optional)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}