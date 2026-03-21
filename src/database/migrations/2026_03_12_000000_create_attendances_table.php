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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
        });

        Schema::create('break_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('break_times');
        Schema::dropIfExists('attendances');
    }
};
