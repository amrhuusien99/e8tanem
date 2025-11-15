<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed a default admin user.
     */
    public function run(): void
    {
        $name = env('ADMIN_NAME', 'Admin User');
        $email = env('ADMIN_EMAIL', 'admin@e8tanem.com');
        $password = env('ADMIN_PASSWORD', 'password123');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );
    }
}

