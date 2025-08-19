<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
        'expires_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'expires_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, $userId)
    {
        // Check if notifications are for a specific user or for all users (user_id is null)
        return $query->where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereNull('user_id'); // Include notifications for all users
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now()); // Only include non-expired notifications
            });
    }
}
