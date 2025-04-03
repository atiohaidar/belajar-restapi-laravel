<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintCategory extends Model
{
    use HasFactory, HasUuids;

    // Disable Laravel's default timestamps if not used in migration
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    // Define relationship: A category has many complaints
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'category_id');
    }
}