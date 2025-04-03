<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Jika menggunakan Sanctum
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    // Order traits alphabetically for consistency
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password', // Handled separately during creation/update
        'phone',
        'role',
        'last_login',
        'last_active',
        'agency_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime', // Standard Laravel field
        'password' => 'hashed', // Standard Laravel cast for hashing
        'last_login' => 'datetime',
        'last_active' => 'datetime',
    ];

    // Relationship: User belongs to an agency (optional)
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    // Relationship: User reports/files many complaints
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'user_id');
    }

    // Relationship: User writes many comments
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'user_id');
    }

    // Relationship: User performs many follow-ups
    public function followUps(): HasMany
    {
        return $this->hasMany(ComplaintFollowUp::class, 'user_id');
    }

    // Relationship: User receives many notifications
    public function notifications(): HasMany
    {
        // Or use Laravel's built-in Notifiable trait methods
        return $this->hasMany(Notification::class, 'user_id');
    }

    // Relationship: User performs many log actions
    public function logs(): HasMany
    {
        return $this->hasMany(ComplaintLog::class, 'user_id');
    }

    // Relationship: User initiates many transfers
    public function transfersInitiated(): HasMany
    {
        return $this->hasMany(ComplaintTransfer::class, 'user_id');
    }

    // Relationship: User gives many ratings
    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(Rating::class, 'user_id');
    }
}