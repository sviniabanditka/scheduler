<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            TeacherSeeder::class,
            CourseSeeder::class,
            GroupSeeder::class,
            SubjectSeeder::class,
            ScheduleSeeder::class,
        ]);
    }
}