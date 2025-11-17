<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'email_verified') || !Schema::hasColumn('users', 'otp_code')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'email_verified')) {
                    $table->boolean('email_verified')->default(false);
                }

                if (!Schema::hasColumn('users', 'otp_code')) {
                    $table->string('otp_code')->nullable();
                }
            });
        }
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'otp_code')) {
                $table->dropColumn('otp_code');
            }

            if (Schema::hasColumn('users', 'email_verified')) {
                $table->dropColumn('email_verified');
            }
        });
    }
    
};
