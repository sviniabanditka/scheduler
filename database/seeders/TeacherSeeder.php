<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Teacher;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = [
            [
                'name' => 'Иванов Иван Иванович',
                'email' => 'ivanov@university.edu',
                'phone' => '+7 (495) 123-45-67',
            ],
            [
                'name' => 'Петрова Анна Сергеевна',
                'email' => 'petrova@university.edu',
                'phone' => '+7 (495) 234-56-78',
            ],
            [
                'name' => 'Сидоров Петр Александрович',
                'email' => 'sidorov@university.edu',
                'phone' => '+7 (495) 345-67-89',
            ],
            [
                'name' => 'Козлова Мария Владимировна',
                'email' => 'kozlova@university.edu',
                'phone' => '+7 (495) 456-78-90',
            ],
            [
                'name' => 'Морозов Дмитрий Николаевич',
                'email' => 'morozov@university.edu',
                'phone' => '+7 (495) 567-89-01',
            ],
        ];

        foreach ($teachers as $teacher) {
            Teacher::create($teacher);
        }
    }
}
