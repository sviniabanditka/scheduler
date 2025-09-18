<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'name' => 'Программная инженерия',
                'number' => 1,
            ],
            [
                'name' => 'Информационные системы',
                'number' => 2,
            ],
            [
                'name' => 'Кибербезопасность',
                'number' => 3,
            ],
            [
                'name' => 'Искусственный интеллект',
                'number' => 4,
            ],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
