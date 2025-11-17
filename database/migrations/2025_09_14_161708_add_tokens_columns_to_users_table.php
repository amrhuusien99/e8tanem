<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasColumn('users', 'access_token_expires_at')
            || !Schema::hasColumn('users', 'refresh_token')
            || !Schema::hasColumn('users', 'refresh_token_expires_at')
        ) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'access_token_expires_at')) {
                    $table->timestamp('access_token_expires_at')->nullable()->after('remember_token');
                }

                if (!Schema::hasColumn('users', 'refresh_token')) {
                    $table->string('refresh_token', 255)->nullable()->after('access_token_expires_at');
                }

                if (!Schema::hasColumn('users', 'refresh_token_expires_at')) {
                    $table->timestamp('refresh_token_expires_at')->nullable()->after('refresh_token');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('users', 'access_token_expires_at')) {
                $columns[] = 'access_token_expires_at';
            }

            if (Schema::hasColumn('users', 'refresh_token')) {
                $columns[] = 'refresh_token';
            }

            if (Schema::hasColumn('users', 'refresh_token_expires_at')) {
                $columns[] = 'refresh_token_expires_at';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};