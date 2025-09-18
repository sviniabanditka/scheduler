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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->integer('day_of_week'); // 1-7 (Monday-Sunday)
            $table->string('time_slot'); // e.g. "09:00-10:30"
            $table->integer('week_number')->nullable(); // week number (for alternating classes)
            $table->string('classroom')->nullable();
            $table->timestamps();
            
            // Indexes for query optimization
            $table->index(['group_id', 'day_of_week', 'time_slot']);
            $table->index(['teacher_id', 'day_of_week', 'time_slot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
