<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Link User to Teacher entity
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->nullable()->after('role');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');
            $table->index('teacher_id', 'users_teacher_id_index');
        });

        // Teacher preference rules (expressive rule system)
        Schema::create('teacher_preference_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->string('rule_type', 50);
            $table->jsonb('params');
            $table->smallInteger('priority')->default(0);
            $table->smallInteger('weight')->default(10);
            $table->boolean('is_active')->default(true);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'teacher_preference_rules_tenant_id_index');
            $table->index(['teacher_id', 'rule_type'], 'teacher_pref_rules_teacher_type_index');
        });

        // Reschedule requests from teachers
        Schema::create('reschedule_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->unsignedBigInteger('assignment_id');
            $table->foreign('assignment_id')->references('id')->on('schedule_assignments')->onDelete('cascade');
            $table->smallInteger('proposed_day_of_week');
            $table->smallInteger('proposed_slot_index');
            $table->enum('proposed_parity', ['both', 'num', 'den'])->default('both');
            $table->unsignedBigInteger('proposed_room_id')->nullable();
            $table->foreign('proposed_room_id')->references('id')->on('rooms')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('teacher_comment')->nullable();
            $table->text('admin_comment')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'reschedule_requests_tenant_id_index');
            $table->index(['teacher_id', 'status'], 'reschedule_requests_teacher_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reschedule_requests');
        Schema::dropIfExists('teacher_preference_rules');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropIndex('users_teacher_id_index');
            $table->dropColumn('teacher_id');
        });
    }
};
