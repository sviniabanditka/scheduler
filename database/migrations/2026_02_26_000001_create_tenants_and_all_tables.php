<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function ($t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('subdomain')->unique();
            $t->string('domain')->unique();
            $t->jsonb('settings')->nullable();
            $t->timestamps();
        });

        // Add tenant_id and missing columns to tables created by old migrations
        Schema::table('teachers', function ($t) {
            $t->uuid('tenant_id')->nullable()->after('id');
        });

        Schema::table('subjects', function ($t) {
            $t->uuid('tenant_id')->nullable()->after('id');
        });

        Schema::table('courses', function ($t) {
            $t->uuid('tenant_id')->nullable()->after('id');
        });

        Schema::table('groups', function ($t) {
            $t->uuid('tenant_id')->nullable()->after('id');
            $t->string('code', 20)->nullable()->after('name');
            $t->integer('size')->default(0)->after('code');
            $t->integer('semester')->nullable()->after('size');
            $t->string('program')->nullable()->after('semester');
            $t->boolean('active')->default(true)->after('program');
        });

        Schema::create('rooms', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->string('code', 20);
            $t->string('title', 100);
            $t->integer('capacity')->default(0);
            $t->enum('room_type', ['lecture', 'lab', 'seminar', 'pc', 'gym', 'other'])->default('lecture');
            $t->jsonb('features')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('calendars', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->string('name', 120);
            $t->date('start_date');
            $t->date('end_date');
            $t->integer('weeks')->default(16);
            $t->boolean('parity_enabled')->default(false);
            $t->integer('days_per_week')->default(6);
            $t->integer('slots_per_day')->default(6);
            $t->integer('slot_duration_minutes')->default(90);
            $t->integer('break_duration_minutes')->default(10);
            $t->timestamps();
        });

        Schema::create('time_slots', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('calendar_id')->constrained()->onDelete('cascade');
            $t->smallInteger('day_of_week');
            $t->smallInteger('slot_index');
            $t->time('start_time');
            $t->time('end_time');
            $t->enum('parity', ['both', 'num', 'den'])->default('both');
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });

        Schema::create('activities', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('subject_id')->constrained()->onDelete('cascade');
            $t->string('title', 200)->nullable();
            $t->enum('activity_type', ['lecture', 'lab', 'seminar', 'practice', 'pc'])->default('lecture');
            $t->smallInteger('duration_slots')->default(1);
            $t->smallInteger('required_slots_per_period')->default(1);
            $t->foreignId('calendar_id')->constrained()->onDelete('cascade');
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('activity_groups', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('activity_id')->constrained()->onDelete('cascade');
            $t->foreignId('group_id')->constrained()->onDelete('cascade');
            $t->timestamps();
        });

        Schema::create('activity_teachers', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('activity_id')->constrained()->onDelete('cascade');
            $t->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $t->timestamps();
        });

        Schema::create('activity_room_types', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('activity_id')->constrained()->onDelete('cascade');
            $t->enum('room_type', ['lecture', 'lab', 'seminar', 'pc', 'gym', 'other']);
            $t->timestamps();
        });

        Schema::create('teacher_unavailability', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $t->foreignId('calendar_id')->constrained()->onDelete('cascade');
            $t->smallInteger('day_of_week');
            $t->smallInteger('slot_index');
            $t->enum('parity', ['both', 'num', 'den'])->default('both');
            $t->text('reason')->nullable();
            $t->timestamps();
        });

        Schema::create('room_unavailability', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('room_id')->constrained()->onDelete('cascade');
            $t->foreignId('calendar_id')->constrained()->onDelete('cascade');
            $t->smallInteger('day_of_week');
            $t->smallInteger('slot_index');
            $t->enum('parity', ['both', 'num', 'den'])->default('both');
            $t->text('reason')->nullable();
            $t->timestamps();
        });

        Schema::create('group_unavailability', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('group_id')->constrained()->onDelete('cascade');
            $t->foreignId('calendar_id')->constrained()->onDelete('cascade');
            $t->smallInteger('day_of_week');
            $t->smallInteger('slot_index');
            $t->enum('parity', ['both', 'num', 'den'])->default('both');
            $t->text('reason')->nullable();
            $t->timestamps();
        });

        Schema::create('teacher_preferences', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $t->smallInteger('day_of_week');
            $t->smallInteger('slot_index');
            $t->enum('parity', ['both', 'num', 'den'])->default('both');
            $t->smallInteger('weight')->default(0);
            $t->timestamps();
        });

        Schema::create('soft_weights', function ($t) {
            $t->uuid('tenant_id')->primary();
            $t->integer('w_windows')->default(10);
            $t->integer('w_prefs')->default(5);
            $t->integer('w_balance')->default(2);
            $t->timestamps();
        });

        Schema::create('schedule_versions', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('calendar_id')->constrained()->onDelete('cascade');
            $t->string('name', 120);
            $t->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $t->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $t->unsignedBigInteger('parent_version_id')->nullable();
            $t->integer('version_number')->default(1);
            $t->integer('random_seed')->nullable();
            $t->jsonb('generation_params')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::create('schedule_assignments', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('schedule_version_id')->constrained('schedule_versions')->onDelete('cascade');
            $t->foreignId('activity_id')->constrained()->onDelete('cascade');
            $t->smallInteger('day_of_week');
            $t->smallInteger('slot_index');
            $t->enum('parity', ['both', 'num', 'den'])->default('both');
            $t->foreignId('room_id')->constrained()->onDelete('restrict');
            $t->boolean('locked')->default(false);
            $t->enum('source', ['solver', 'manual'])->default('solver');
            $t->timestamps();
        });

        Schema::create('violations', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('schedule_version_id')->constrained('schedule_versions')->onDelete('cascade');
            $t->foreignId('activity_id')->nullable()->constrained()->onDelete('set null');
            $t->string('code', 50);
            $t->enum('severity', ['hard', 'soft'])->default('soft');
            $t->jsonb('meta')->nullable();
            $t->timestamps();
        });

        Schema::create('import_jobs', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('user_id')->constrained()->onDelete('restrict');
            $t->enum('kind', ['csv', 'xlsx', 'ics']);
            $t->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $t->jsonb('stats')->nullable();
            $t->text('error_message')->nullable();
            $t->string('file_path', 500)->nullable();
            $t->timestamps();
        });

        Schema::create('audit_logs', function ($t) {
            $t->id();
            $t->uuid('tenant_id');
            $t->foreignId('actor_user_id')->nullable()->constrained('users')->onDelete('set null');
            $t->string('action', 50);
            $t->string('entity', 50);
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->jsonb('meta')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('violations');
        Schema::dropIfExists('schedule_assignments');
        Schema::dropIfExists('schedule_versions');
        Schema::dropIfExists('soft_weights');
        Schema::dropIfExists('teacher_preferences');
        Schema::dropIfExists('group_unavailability');
        Schema::dropIfExists('room_unavailability');
        Schema::dropIfExists('teacher_unavailability');
        Schema::dropIfExists('activity_room_types');
        Schema::dropIfExists('activity_teachers');
        Schema::dropIfExists('activity_groups');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('time_slots');
        Schema::dropIfExists('calendars');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('tenants');
    }
};
