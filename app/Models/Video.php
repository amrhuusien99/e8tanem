<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


use Illuminate\Support\Facades\DB;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'video_url',
        'thumbnail_url',
        'is_active',
        'views_count',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'views_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($video) {
            // Delete related comments and likes first
            $video->comments()->delete();
            $video->likes()->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function lastComment(): HasOne
    {
        return $this->hasOne(Comment::class)
            ->where('is_active', true)
            ->latestOfMany('created_at');
    }

    /**
     * Scope a query to order videos using a hybrid "For You" style ranking.
     *
     * The score blends recency, engagement quality, baseline reach and a light, deterministic
     * shuffle so users with the same seed get a consistent but non-static ordering.
     */
    public function scopeFeedRanked(Builder $query, ?string $seed = null): Builder
    {
        $seedValue = abs(crc32($seed ?? uniqid('', true)));
        $prime = 9973;
        $multiplier = ($seedValue % $prime) + 1;

        $table = $query->getModel()->getTable();
        $connection = $query->getModel()->getConnectionName();
        $driver = DB::connection($connection)->getDriverName();

        if ($driver === 'sqlite') {
            $hoursSince = sprintf(
                '( (strftime(\'%%s\', \'now\') - strftime(\'%%s\', %s.created_at)) / 3600.0 )',
                $table
            );
            $viewsSafeExpr = 'MAX(COALESCE(views_count, 0), 1)';
        } else {
            $hoursSince = sprintf(
                'GREATEST(TIMESTAMPDIFF(HOUR, %s.created_at, NOW()), 0)',
                $table
            );
            $viewsSafeExpr = 'GREATEST(COALESCE(views_count, 0), 1)';
        }

        $recencyExpr = "(1.0 / ({$hoursSince} + 1))";
        $viralityExpr = '('
            . '(COALESCE(likes_count, 0) * 1.5 + COALESCE(comments_count, 0) * 2)'
            . "/ {$viewsSafeExpr}"
            . ')';
        $baselineExpr = '(LEAST(COALESCE(views_count, 0), 5000) / 5000.0)';
        $randomExpr = sprintf(
            '((((%1$d * %2$s.id) %% %3$d) + %3$d) %% %3$d) / %3$d.0',
            $multiplier,
            $table,
            $prime
        );

        $scoreExpr = sprintf(
            '((%s * 0.4) + (%s * 0.4) + (%s * 0.15) + (%s * 0.05))',
            $recencyExpr,
            $viralityExpr,
            $baselineExpr,
            $randomExpr
        );

        return $query
            ->addSelect(DB::raw("{$scoreExpr} as ranking_score"))
            ->orderByDesc('ranking_score')
            ->orderByDesc("{$table}.created_at");
    }
}
