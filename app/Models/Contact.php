<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class   Contact extends Model
{
    use HasFactory;
    protected $table = "contacts";
    protected $keyType = "int";
    protected $primaryKey = "id";
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'phone',
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function address(): HasMany
    {
        return $this->hasMany(Address::class, "contact_id", "id");
    }

}
