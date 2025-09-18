<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Group;
use App\Models\Subject;
use App\Models\Teacher;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = Group::all();
        $subjects = Subject::all();
        $teachers = Teacher::all();
        
        $timeSlots = array_keys(Schedule::TIME_SLOTS);
        $daysOfWeek = array_keys(Schedule::DAYS_OF_WEEK);
        $classrooms = ['101', '102', '103', '201', '202', '203', '301', '302', '303'];
        
        // Создаем расписание для каждой группы
        foreach ($groups as $group) {
            // Для каждой недели года (1-52)
            for ($week = 1; $week <= 52; $week++) {
                // Вычисляем количество занятий для недели (минимум 50% от доступных слотов)
                $totalSlots = count($timeSlots) * count($daysOfWeek); // 7 дней × 7 слотов = 49 слотов
                $minLessons = ceil($totalSlots * 0.5); // Минимум 50% = 25 занятий
                $maxLessons = ceil($totalSlots * 0.7); // Максимум 70% = 35 занятий
                $numberOfLessons = rand($minLessons, $maxLessons);
                
                // Создаем массив всех возможных комбинаций день-время
                $allSlots = [];
                foreach ($daysOfWeek as $day) {
                    foreach ($timeSlots as $time) {
                        $allSlots[] = ['day' => $day, 'time' => $time];
                    }
                }
                
                // Перемешиваем слоты для случайного распределения
                shuffle($allSlots);
                
                // Берем первые N слотов для занятий
                $selectedSlots = array_slice($allSlots, 0, $numberOfLessons);
                
                // Создаем занятия для выбранных слотов
                foreach ($selectedSlots as $slot) {
                    $subject = $subjects->random();
                    $teacher = $teachers->random();
                    $classroom = $classrooms[array_rand($classrooms)];
                    
                    // Проверяем, нет ли уже занятия в это время для этой группы в эту неделю
                    $existingSchedule = Schedule::where('group_id', $group->id)
                        ->where('day_of_week', $slot['day'])
                        ->where('time_slot', $slot['time'])
                        ->where('week_number', $week)
                        ->first();
                    
                    if (!$existingSchedule) {
                        Schedule::create([
                            'group_id' => $group->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher->id,
                            'day_of_week' => $slot['day'],
                            'time_slot' => $slot['time'],
                            'week_number' => $week,
                            'classroom' => $classroom,
                        ]);
                    }
                }
            }
        }
    }
}
