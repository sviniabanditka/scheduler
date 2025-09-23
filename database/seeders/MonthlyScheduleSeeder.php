<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Group;
use App\Models\Subject;
use App\Models\Teacher;

class MonthlyScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Используем текущий месяц и год
        $year = date('Y');
        $month = date('m');
        
        $this->generateScheduleForMonth($year, $month);
    }
    
    /**
     * Устанавливает команду для вывода информации
     */
    public function setCommand($command): void
    {
        $this->command = $command;
    }
    
    /**
     * Генерирует расписание для конкретного месяца
     */
    public function generateScheduleForMonth(int $year, int $month): void
    {
        $groups = Group::all();
        $subjects = Subject::all();
        $teachers = Teacher::all();
        
        if ($groups->isEmpty() || $subjects->isEmpty() || $teachers->isEmpty()) {
            $this->command->error('Необходимо сначала создать группы, предметы и преподавателей!');
            return;
        }
        
        $timeSlots = array_keys(Schedule::TIME_SLOTS);
        $classrooms = ['101', '102', '103', '201', '202', '203', '301', '302', '303'];
        
        // Получаем первый и последний день месяца
        $firstDayOfMonth = new \DateTime("{$year}-{$month}-01");
        $lastDayOfMonth = new \DateTime($firstDayOfMonth->format('Y-m-t'));
        
        $this->command->info("Генерируем расписание для {$firstDayOfMonth->format('F Y')}...");
        
        $totalLessons = 0;
        
        // Создаем расписание для каждой группы
        foreach ($groups as $group) {
            $groupLessons = $this->createGroupMonthlySchedule(
                $group, 
                $subjects, 
                $teachers, 
                $classrooms, 
                $timeSlots, 
                $firstDayOfMonth, 
                $lastDayOfMonth
            );
            $totalLessons += $groupLessons;
            
            $this->command->info("Создано {$groupLessons} занятий для группы {$group->name}");
        }
        
        $this->command->info("Всего создано {$totalLessons} занятий для {$groups->count()} групп");
    }
    
    /**
     * Создает расписание для одной группы на месяц
     */
    private function createGroupMonthlySchedule($group, $subjects, $teachers, $classrooms, $timeSlots, $firstDay, $lastDay): int
    {
        $lessonsCreated = 0;
        $currentDay = clone $firstDay;
        
        while ($currentDay <= $lastDay) {
            $dayOfWeek = $currentDay->format('N'); // 1 = Monday, 7 = Sunday
            
            // Создаем занятия только в рабочие дни
            if ($dayOfWeek <= 5) { // Понедельник - Пятница
                // Количество занятий в день (2-5)
                $numberOfLessons = rand(2, 5);
                
                // Выбираем случайные временные слоты (исключаем слишком ранние и поздние)
                $availableSlots = array_filter($timeSlots, function($slot) {
                    return !in_array($slot, ['08:00-09:30', '18:30-20:00']);
                });
                
                $selectedSlots = array_rand($availableSlots, min($numberOfLessons, count($availableSlots)));
                if (!is_array($selectedSlots)) {
                    $selectedSlots = [$selectedSlots];
                }
                
                foreach ($selectedSlots as $slotIndex) {
                    $timeSlot = $availableSlots[$slotIndex];
                    $subject = $subjects->random();
                    $teacher = $teachers->random();
                    $classroom = $classrooms[array_rand($classrooms)];
                    
                    // Проверяем, нет ли уже занятия в это время в этот день
                    $existingSchedule = Schedule::where('group_id', $group->id)
                        ->where('day_of_week', $dayOfWeek)
                        ->where('time_slot', $timeSlot)
                        ->where('date', $currentDay->format('Y-m-d'))
                        ->first();
                    
                    if (!$existingSchedule) {
                        Schedule::create([
                            'group_id' => $group->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher->id,
                            'day_of_week' => $dayOfWeek,
                            'time_slot' => $timeSlot,
                            'week_number' => null,
                            'date' => $currentDay->format('Y-m-d'),
                            'classroom' => $classroom,
                        ]);
                        
                        $lessonsCreated++;
                    }
                }
            }
            
            $currentDay->add(new \DateInterval('P1D'));
        }
        
        return $lessonsCreated;
    }
}
