<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    /**
     * Показать главную страницу с расписанием
     */
    public function index()
    {
        $courses = Course::orderBy('number')->get();
        return view('schedule', compact('courses'));
    }

    /**
     * Получить группы для выбранного курса
     */
    public function getCourseGroups(int $courseId): JsonResponse
    {
        $groups = Group::where('course_id', $courseId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($groups);
    }

    /**
     * Получить расписание для группы и недели
     */
    public function getSchedule(int $groupId, int $week): JsonResponse
    {
        $schedules = Schedule::with(['subject', 'teacher', 'group.course'])
            ->where('group_id', $groupId)
            ->where('week_number', $week)
            ->get();

        // Создаем матрицу расписания (день × время)
        $scheduleMatrix = [];
        $timeSlots = array_keys(Schedule::TIME_SLOTS);
        $daysOfWeek = array_keys(Schedule::DAYS_OF_WEEK);

        // Инициализируем пустую матрицу
        foreach ($daysOfWeek as $day) {
            foreach ($timeSlots as $time) {
                $scheduleMatrix[$day][$time] = null;
            }
        }

        // Заполняем матрицу данными из базы
        foreach ($schedules as $schedule) {
            $scheduleMatrix[$schedule->day_of_week][$schedule->time_slot] = [
                'id' => $schedule->id,
                'subject' => $schedule->subject->name,
                'subject_type' => $schedule->subject->type,
                'teacher' => $schedule->teacher->name,
                'classroom' => $schedule->classroom,
                'week_number' => $schedule->week_number,
            ];
        }

        return response()->json([
            'schedule' => $scheduleMatrix,
            'time_slots' => Schedule::TIME_SLOTS,
            'days_of_week' => Schedule::DAYS_OF_WEEK,
            'subject_types' => \App\Models\Subject::TYPES,
        ]);
    }

    /**
     * Получить список недель с датами
     */
    public function getWeeks(): JsonResponse
    {
        $weeks = [];
        $currentYear = date('Y');
        $startDate = new \DateTime("{$currentYear}-01-01");
        
        // Находим первый понедельник года
        while ($startDate->format('N') != 1) {
            $startDate->add(new \DateInterval('P1D'));
        }
        
        for ($week = 1; $week <= 52; $week++) {
            $endDate = clone $startDate;
            $endDate->add(new \DateInterval('P6D')); // +6 дней = воскресенье
            
            $weeks[] = [
                'number' => $week,
                'start_date' => $startDate->format('d.m.Y'),
                'end_date' => $endDate->format('d.m.Y'),
                'label' => "Неделя {$week} ({$startDate->format('d.m')} - {$endDate->format('d.m.Y')})"
            ];
            
            $startDate->add(new \DateInterval('P7D')); // +7 дней = следующая неделя
        }
        
        return response()->json($weeks);
    }

    /**
     * Получить список всех курсов
     */
    public function getCourses(): JsonResponse
    {
        $courses = Course::orderBy('number')->get(['id', 'name', 'number']);
        return response()->json($courses);
    }

    /**
     * Получить список всех предметов
     */
    public function getSubjects(): JsonResponse
    {
        $subjects = Subject::with('teacher')->get(['id', 'name', 'type', 'teacher_id']);
        return response()->json($subjects);
    }

    /**
     * Получить список всех преподавателей
     */
    public function getTeachers(): JsonResponse
    {
        $teachers = Teacher::orderBy('name')->get(['id', 'name']);
        return response()->json($teachers);
    }

    /**
     * Создать новое занятие
     */
    public function storeSchedule(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'group_id' => 'required|exists:groups,id',
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'required|exists:teachers,id',
                'day_of_week' => 'required|integer|min:1|max:7',
                'time_slot' => 'required|string',
                'week_number' => 'nullable|integer|min:1|max:52',
                'classroom' => 'nullable|string|max:50',
            ]);

            // Проверяем конфликты
            $this->validateScheduleConflicts($request->all());

            $schedule = Schedule::create($request->all());
            $schedule->load(['subject', 'teacher', 'group']);

            return response()->json([
                'success' => true,
                'message' => 'Занятие успешно создано',
                'schedule' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Обновить занятие
     */
    public function updateSchedule(Request $request, int $id): JsonResponse
    {
        try {
            $schedule = Schedule::findOrFail($id);

            $request->validate([
                'group_id' => 'required|exists:groups,id',
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'required|exists:teachers,id',
                'day_of_week' => 'required|integer|min:1|max:7',
                'time_slot' => 'required|string',
                'week_number' => 'nullable|integer|min:1|max:52',
                'classroom' => 'nullable|string|max:50',
            ]);

            // Проверяем конфликты (исключая текущее занятие)
            $this->validateScheduleConflicts($request->all(), $id);

            $schedule->update($request->all());
            $schedule->load(['subject', 'teacher', 'group']);

            return response()->json([
                'success' => true,
                'message' => 'Занятие успешно обновлено',
                'schedule' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Удалить занятие
     */
    public function deleteSchedule(int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Занятие успешно удалено'
        ]);
    }

    /**
     * Проверить конфликты расписания
     */
    private function validateScheduleConflicts(array $data, ?int $excludeId = null): void
    {
        // Проверяем конфликт группы
        $groupQuery = Schedule::where('group_id', $data['group_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('time_slot', $data['time_slot']);

        if (isset($data['week_number']) && $data['week_number'] !== null && $data['week_number'] !== '') {
            $groupQuery->where('week_number', $data['week_number']);
        } else {
            $groupQuery->whereNull('week_number');
        }

        if ($excludeId) {
            $groupQuery->where('id', '!=', $excludeId);
        }

        if ($groupQuery->exists()) {
            throw new \Exception('Конфликт расписания: группа уже имеет занятие в это время');
        }

        // Проверяем конфликт преподавателя
        $teacherQuery = Schedule::where('teacher_id', $data['teacher_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('time_slot', $data['time_slot']);

        if (isset($data['week_number']) && $data['week_number'] !== null && $data['week_number'] !== '') {
            $teacherQuery->where('week_number', $data['week_number']);
        } else {
            $teacherQuery->whereNull('week_number');
        }

        if ($excludeId) {
            $teacherQuery->where('id', '!=', $excludeId);
        }

        if ($teacherQuery->exists()) {
            throw new \Exception('Конфликт расписания: преподаватель уже занят в это время');
        }
    }
}
