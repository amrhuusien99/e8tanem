<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PasswordResetCode extends Model
{
    protected $fillable = ['email', 'code', 'expires_at', 'used', 'reset_token'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    public static function generateCode(): string
    {
        return str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function createCodeForEmail(string $email): self
    {
        return self::create([
            'email' => $email,
            'code' => self::generateCode(),
            'expires_at' => Carbon::now()->addMinutes(15),
            'used' => false,
        ]);
    }

    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    public function markUsedWithToken(string $token): void
    {
        $this->update([
            'used' => true,
            'reset_token' => $token
        ]);
    }
}
