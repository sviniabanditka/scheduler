<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soft_weights', function (Blueprint $table) {
            $table->integer('w_teacher_windows')->default(1)->after('w_windows');
        });
    }

    public function down(): void
    {
        Schema::table('soft_weights', function (Blueprint $table) {
            $table->dropColumn('w_teacher_windows');
        });
    }
};
