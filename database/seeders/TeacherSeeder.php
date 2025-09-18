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
                'name' => 'Іванов Іван Іванович',
                'email' => 'ivanov@university.edu',
                'phone' => '+380 (67) 123-45-67',
            ],
            [
                'name' => 'Петрова Анна Сергіївна',
                'email' => 'petrova@university.edu',
                'phone' => '+380 (67) 234-56-78',
            ],
            [
                'name' => 'Сидоров Петро Олександрович',
                'email' => 'sidorov@university.edu',
                'phone' => '+380 (67) 345-67-89',
            ],
            [
                'name' => 'Козлова Марія Володимирівна',
                'email' => 'kozlova@university.edu',
                'phone' => '+380 (67) 456-78-90',
            ],
            [
                'name' => 'Морозов Дмитро Миколайович',
                'email' => 'morozov@university.edu',
                'phone' => '+380 (67) 567-89-01',
            ],
        ];

        foreach ($teachers as $teacher) {
            Teacher::create($teacher);
        }
    }
}
