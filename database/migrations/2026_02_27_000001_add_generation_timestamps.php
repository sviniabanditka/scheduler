<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_versions', function (Blueprint $table) {
            $table->timestamp('generation_started_at')->nullable();
            $table->timestamp('generation_finished_at')->nullable();
        });

        // Expand the status check constraint to include 'generating' and 'failed'
        DB::statement("ALTER TABLE schedule_versions DROP CONSTRAINT IF EXISTS schedule_versions_status_check");
        DB::statement("ALTER TABLE schedule_versions ADD CONSTRAINT schedule_versions_status_check CHECK (status IN ('draft', 'published', 'archived', 'generating', 'failed'))");
    }

    public function down(): void
    {
        // Revert status constraint to original values
        DB::statement("ALTER TABLE schedule_versions DROP CONSTRAINT IF EXISTS schedule_versions_status_check");
        DB::statement("ALTER TABLE schedule_versions ADD CONSTRAINT schedule_versions_status_check CHECK (status IN ('draft', 'published', 'archived'))");

        Schema::table('schedule_versions', function (Blueprint $table) {
            $table->dropColumn(['generation_started_at', 'generation_finished_at']);
        });
    }
};
