<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintTransfer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'complaint_id',
        'from_agency_id',
        'to_agency_id',
        'user_id',
        'reason',
    ];

     protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship: Transfer belongs to a complaint
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    // Relationship: Transfer is from an agency
    public function fromAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'from_agency_id');
    }

    // Relationship: Transfer is to an agency
    public function toAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'to_agency_id');
    }

    // Relationship: Transfer was initiated by a user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}