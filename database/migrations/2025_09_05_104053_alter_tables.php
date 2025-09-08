<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'verification_code')) {
                $table->string('verification_code')->nullable();
            }
            if (!Schema::hasColumn('users', 'verification_expires_at')) {
                $table->timestamp('verification_expires_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'is_verified')) {
                $table->tinyInteger('is_verified')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'verification_code')) {
                $table->dropColumn('verification_code');
            }
            if (Schema::hasColumn('users', 'verification_expires_at')) {
                $table->dropColumn('verification_expires_at');
            }
            if (Schema::hasColumn('users', 'is_verified')) {
                $table->dropColumn('is_verified');
            }
        });
    }
};
