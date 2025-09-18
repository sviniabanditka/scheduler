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
                'name' => 'Програмна інженерія',
                'number' => 1,
            ],
            [
                'name' => 'Інформаційні системи',
                'number' => 2,
            ],
            [
                'name' => 'Кібербезпека',
                'number' => 3,
            ],
            [
                'name' => 'Штучний інтелект',
                'number' => 4,
            ],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
