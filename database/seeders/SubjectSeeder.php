<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Teacher;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = Teacher::all();
        
        $subjects = [
            [
                'name' => 'Програмування на Python',
                'type' => Subject::TYPE_LECTURE,
            ],
            [
                'name' => 'Програмування на Python',
                'type' => Subject::TYPE_PRACTICE,
            ],
            [
                'name' => 'Бази даних',
                'type' => Subject::TYPE_LECTURE,
            ],
            [
                'name' => 'Бази даних',
                'type' => Subject::TYPE_PRACTICE,
            ],
            [
                'name' => 'Веб-розробка',
                'type' => Subject::TYPE_LECTURE,
            ],
            [
                'name' => 'Веб-розробка',
                'type' => Subject::TYPE_PRACTICE,
            ],
            [
                'name' => 'Алгоритми та структури даних',
                'type' => Subject::TYPE_LECTURE,
            ],
            [
                'name' => 'Алгоритми та структури даних',
                'type' => Subject::TYPE_PRACTICE,
            ],
            [
                'name' => 'Машинне навчання',
                'type' => Subject::TYPE_LECTURE,
            ],
            [
                'name' => 'Машинне навчання',
                'type' => Subject::TYPE_PRACTICE,
            ],
        ];

        foreach ($subjects as $index => $subjectData) {
            Subject::create([
                'name' => $subjectData['name'],
                'type' => $subjectData['type'],
                'teacher_id' => $teachers[$index % $teachers->count()]->id,
            ]);
        }
    }
}
