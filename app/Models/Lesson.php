<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'title',
        'description',
        'video_path',
        'thumbnail',
        'duration',
        'order',
        'is_active',
        'views_count'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration' => 'integer',
        'order' => 'integer',
        'views_count' => 'integer'
    ];

    protected $appends = ['video_url', 'thumbnail_url'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->video_path) {
            return null;
        }
        
        // Remove 'public/' prefix if it exists and return relative path
        return str_replace('public/', '', $this->video_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) {
            return null;
        }
        
        // Ensure the path is relative to public storage
        $path = str_replace('public/', '', $this->thumbnail);
        return Storage::disk('public')->url($path);
    }
}
