<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'parent_id',
    ];

    // Relationship: Agency can belong to a parent agency
    public function parentAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'parent_id');
    }

    // Relationship: Agency can have many child agencies
    public function childAgencies(): HasMany
    {
        return $this->hasMany(Agency::class, 'parent_id');
    }

    // Relationship: Agency has many users (Managers, Staff)
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'agency_id');
    }

    // Relationship: Agency is assigned many complaints
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'agency_id');
    }

    // Relationship: Agency handles follow-ups (optional if user's agency is sufficient)
    public function followUps(): HasMany
    {
        return $this->hasMany(ComplaintFollowUp::class, 'agency_id');
    }

    // Relationship: Agency is the source of transfers
    public function transfersFrom(): HasMany
    {
        return $this->hasMany(ComplaintTransfer::class, 'from_agency_id');
    }

    // Relationship: Agency is the destination of transfers
    public function transfersTo(): HasMany
    {
        return $this->hasMany(ComplaintTransfer::class, 'to_agency_id');
    }

    // Relationship: Agency receives ratings
    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'agency_id');
    }
}