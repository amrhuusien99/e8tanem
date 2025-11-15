<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOtpAndApiTokenToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // لو عمود otp_code مش موجود
            if (!Schema::hasColumn('users', 'otp_code')) {
                $table->string('otp_code')->nullable()->after('password');
            }

            // لو عمود email_verified مش موجود
            if (!Schema::hasColumn('users', 'email_verified')) {
                $table->boolean('email_verified')->default(false)->after('otp_code');
            }

            // لو عمود api_token مش موجود
            if (!Schema::hasColumn('users', 'api_token')) {
                $table->string('api_token', 80)->unique()->nullable()->default(null)->after('email_verified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'otp_code')) {
                $table->dropColumn('otp_code');
            }

            if (Schema::hasColumn('users', 'email_verified')) {
                $table->dropColumn('email_verified');
            }

            if (Schema::hasColumn('users', 'api_token')) {
                $table->dropColumn('api_token');
            }
        });
    }
}
