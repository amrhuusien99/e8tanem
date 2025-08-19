<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Podcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'audio_url',
        'thumbnail_url',
        'category',
        'description',
        'is_active',
        'plays_count',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'plays_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}