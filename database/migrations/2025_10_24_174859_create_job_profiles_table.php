<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->text('summary')->nullable();

            // My Next Role
            $table->string('preferred_role')->nullable();
            $table->smallInteger('open_to_all_roles')->default(0);
            $table->string('industry_id')->nullable();
            $table->string('preferred_locations')->nullable(); // JSON or comma-separated list
            $table->smallInteger('right_to_work_in_saudi')->default(0);

            // Work Preferences
            $table->enum('minimum_pay_type', ['hourly', 'annual'])->nullable();
            $table->decimal('minimum_pay_amount', 10, 2)->nullable();
            $table->string('notice_period')->nullable();
            $table->string('work_type')->nullable();
            $table->smallInteger('status')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_profiles');
    }
};
