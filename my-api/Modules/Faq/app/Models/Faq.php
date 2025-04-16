<?php

namespace Modules\Faq\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Faq extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'question',
        'answer',
        'is_published',
        'order',
        'category',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the user who created the FAQ.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
