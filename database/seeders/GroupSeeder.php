<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\Course;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::all();
        
        foreach ($courses as $course) {
            // Create 3 groups for each course
            for ($i = 1; $i <= 3; $i++) {
                Group::create([
                    'name' => "{$course->name}-{$i}",
                    'course_id' => $course->id,
                ]);
            }
        }
    }
}
