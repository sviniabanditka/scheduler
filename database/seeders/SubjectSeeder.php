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
                'name' => 'Программирование на Python',
                'type' => Subject::TYPE_LECTURE,
            ],
            [
                'name' => 'Программирование на Python',
                'type' => Subject::TYPE_PRACTICE,
            ],
            [
                'name' => 'Базы данных',
                'type' => Subject::TYPE_LECTURE,
            ],
            [
                'name' => 'Базы данных',
                'type' => Subject::TYPE_PRACTICE,
            ],
            [
                'name' => 'Веб-разработка',
                'type' => Subject::TYPE_LECTURE,
            ],
            [
                'name' => 'Веб-разработка',
                'type' => Subject::TYPE_PRACTICE,
            ],
            [
                'name' => 'Алгоритмы и структуры данных',
                'type' => Subject::TYPE_LECTURE,
            ],
            [
                'name' => 'Алгоритмы и структуры данных',
                'type' => Subject::TYPE_PRACTICE,
            ],
            [
                'name' => 'Машинное обучение',
                'type' => Subject::TYPE_LECTURE,
            ],
            [
                'name' => 'Машинное обучение',
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
