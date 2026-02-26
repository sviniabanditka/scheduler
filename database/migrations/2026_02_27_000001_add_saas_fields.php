<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add tenant_id and role to users
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id', 'users_tenant_id_index');
            $table->enum('role', ['owner', 'admin', 'planner', 'teacher', 'viewer'])
                ->default('viewer')
                ->after('password');
        });

        // Add SaaS fields to tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('public_slug', 64)->unique()->nullable()->after('domain');
            $table->boolean('is_active')->default(true)->after('settings');
        });

        // Add indexes for tenant_id on all tables that need them
        $tables = [
            'rooms', 'calendars', 'time_slots', 'activities',
            'activity_groups', 'activity_teachers', 'activity_room_types',
            'teacher_unavailability', 'room_unavailability', 'group_unavailability',
            'teacher_preferences', 'schedule_versions', 'schedule_assignments',
            'violations', 'import_jobs', 'audit_logs',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->index('tenant_id', "{$tableName}_tenant_id_index");
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex('users_tenant_id_index');
            $table->dropColumn(['tenant_id', 'role']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['public_slug', 'is_active']);
        });

        $tables = [
            'rooms', 'calendars', 'time_slots', 'activities',
            'activity_groups', 'activity_teachers', 'activity_room_types',
            'teacher_unavailability', 'room_unavailability', 'group_unavailability',
            'teacher_preferences', 'schedule_versions', 'schedule_assignments',
            'violations', 'import_jobs', 'audit_logs',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex("{$tableName}_tenant_id_index");
            });
        }
    }
};
