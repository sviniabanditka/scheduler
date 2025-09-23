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
     * Show the main page with schedule
     */
    public function index()
    {
        $courses = Course::orderBy('number')->get();
        return view('schedule', compact('courses'));
    }

    /**
     * Get groups for selected course
     */
    public function getCourseGroups(int $courseId): JsonResponse
    {
        $groups = Group::where('course_id', $courseId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($groups);
    }

    /**
     * Get schedule for group and date range
     */
    public function getSchedule(int $groupId, string $startDate, string $endDate): JsonResponse
    {
        $schedules = Schedule::with(['subject', 'teacher', 'group.course'])
            ->where('group_id', $groupId)
            ->whereNotNull('date')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Create schedule matrix (date × time)
        $scheduleMatrix = [];
        $timeSlots = array_keys(Schedule::TIME_SLOTS);
        
        // Generate date range
        $startDateObj = \Carbon\Carbon::parse($startDate);
        $endDateObj = \Carbon\Carbon::parse($endDate);
        $dateRange = [];
        
        for ($date = $startDateObj->copy(); $date->lte($endDateObj); $date->addDay()) {
            $dateRange[] = $date->format('Y-m-d');
        }

        // Initialize empty matrix
        foreach ($dateRange as $date) {
            foreach ($timeSlots as $time) {
                $scheduleMatrix[$date][$time] = null;
            }
        }

        // Fill matrix with data from database
        foreach ($schedules as $schedule) {
            $scheduleDate = $schedule->date->format('Y-m-d');
            if (isset($scheduleMatrix[$scheduleDate])) {
                $scheduleMatrix[$scheduleDate][$schedule->time_slot] = [
                    'id' => $schedule->id,
                    'subject' => $schedule->subject->name,
                    'subject_type' => $schedule->subject->type,
                    'teacher' => $schedule->teacher->name,
                    'classroom' => $schedule->classroom,
                    'week_number' => $schedule->week_number,
                    'date' => $schedule->date,
                    'formatted_date' => $schedule->date->format('d.m.Y'),
                ];
            }
        }

        // Format date range for frontend
        $formattedDateRange = [];
        foreach ($dateRange as $date) {
            $dateObj = \Carbon\Carbon::parse($date);
            $dayOfWeek = $dateObj->dayOfWeek === 0 ? 7 : $dateObj->dayOfWeek; // Convert Sunday (0) to 7
            $formattedDateRange[] = [
                'date' => $date,
                'formatted' => $dateObj->format('d.m.Y'),
                'day_name' => Schedule::DAYS_OF_WEEK[$dayOfWeek] ?? 'Невідомо',
                'day_of_week' => $dayOfWeek,
            ];
        }

        return response()->json([
            'schedule' => $scheduleMatrix,
            'time_slots' => Schedule::TIME_SLOTS,
            'date_range' => $formattedDateRange,
            'subject_types' => \App\Models\Subject::TYPES,
        ]);
    }

    /**
     * Get current week date range
     */
    public function getCurrentWeekRange(): JsonResponse
    {
        $today = new \DateTime();
        $dayOfWeek = $today->format('N'); // 1 = Monday, 7 = Sunday
        
        // Calculate start of week (Monday)
        $startOfWeek = clone $today;
        $startOfWeek->modify('-' . ($dayOfWeek - 1) . ' days');
        
        // Calculate end of week (Sunday)
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days');
        
        return response()->json([
            'start_date' => $startOfWeek->format('Y-m-d'),
            'end_date' => $endOfWeek->format('Y-m-d'),
            'start_date_formatted' => $startOfWeek->format('d.m.Y'),
            'end_date_formatted' => $endOfWeek->format('d.m.Y'),
            'label' => "Поточна тиждень ({$startOfWeek->format('d.m')} - {$endOfWeek->format('d.m.Y')})"
        ]);
    }

    /**
     * Get list of weeks with dates (kept for backward compatibility)
     */
    public function getWeeks(): JsonResponse
    {
        $weeks = [];
        $currentYear = date('Y');
        $startDate = new \DateTime("{$currentYear}-01-01");
        
        // Find first Monday of the year
        while ($startDate->format('N') != 1) {
            $startDate->add(new \DateInterval('P1D'));
        }
        
        for ($week = 1; $week <= 52; $week++) {
            $endDate = clone $startDate;
            $endDate->add(new \DateInterval('P6D')); // +6 days = Sunday
            
            $weeks[] = [
                'number' => $week,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'start_date_formatted' => $startDate->format('d.m.Y'),
                'end_date_formatted' => $endDate->format('d.m.Y'),
                'label' => "Тиждень {$week} ({$startDate->format('d.m')} - {$endDate->format('d.m.Y')})"
            ];
            
            $startDate->add(new \DateInterval('P7D')); // +7 days = next week
        }
        
        return response()->json($weeks);
    }

    /**
     * Get list of all courses
     */
    public function getCourses(): JsonResponse
    {
        $courses = Course::orderBy('number')->get(['id', 'name', 'number']);
        return response()->json($courses);
    }

    /**
     * Get list of all subjects
     */
    public function getSubjects(): JsonResponse
    {
        $subjects = Subject::with('teacher')->get(['id', 'name', 'type', 'teacher_id']);
        return response()->json($subjects);
    }

    /**
     * Get list of all teachers
     */
    public function getTeachers(): JsonResponse
    {
        $teachers = Teacher::orderBy('name')->get(['id', 'name']);
        return response()->json($teachers);
    }

    /**
     * Create new lesson
     */
    public function storeSchedule(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'group_id' => 'required|exists:groups,id',
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'required|exists:teachers,id',
                'time_slot' => 'required|string',
                'week_number' => 'nullable|integer|min:1|max:52',
                'date' => 'required|date',
                'classroom' => 'nullable|string|max:50',
            ]);

            // Check for conflicts
            $this->validateScheduleConflicts($request->all());

            $data = $request->all();
            $date = \Carbon\Carbon::parse($data['date']);
            $data['day_of_week'] = $date->dayOfWeek === 0 ? 7 : $date->dayOfWeek; // Convert Sunday (0) to 7
            
            $schedule = Schedule::create($data);
            $schedule->load(['subject', 'teacher', 'group']);

            return response()->json([
                'success' => true,
                'message' => 'Заняття успішно створено',
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
     * Update lesson
     */
    public function updateSchedule(Request $request, int $id): JsonResponse
    {
        try {
            $schedule = Schedule::findOrFail($id);

            $request->validate([
                'group_id' => 'required|exists:groups,id',
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'required|exists:teachers,id',
                'time_slot' => 'required|string',
                'week_number' => 'nullable|integer|min:1|max:52',
                'date' => 'required|date',
                'classroom' => 'nullable|string|max:50',
            ]);

            // Check for conflicts (excluding current lesson)
            $this->validateScheduleConflicts($request->all(), $id);

            $data = $request->all();
            $date = \Carbon\Carbon::parse($data['date']);
            $data['day_of_week'] = $date->dayOfWeek === 0 ? 7 : $date->dayOfWeek; // Convert Sunday (0) to 7
            
            $schedule->update($data);
            $schedule->load(['subject', 'teacher', 'group']);

            return response()->json([
                'success' => true,
                'message' => 'Заняття успішно оновлено',
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
     * Delete lesson
     */
    public function deleteSchedule(int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Заняття успішно видалено'
        ]);
    }

    /**
     * Check schedule conflicts
     */
    private function validateScheduleConflicts(array $data, ?int $excludeId = null): void
    {
        // Calculate day_of_week from date
        $date = \Carbon\Carbon::parse($data['date']);
        $dayOfWeek = $date->dayOfWeek === 0 ? 7 : $date->dayOfWeek; // Convert Sunday (0) to 7
        
        // Check group conflict
        $groupQuery = Schedule::where('group_id', $data['group_id'])
            ->where('day_of_week', $dayOfWeek)
            ->where('time_slot', $data['time_slot'])
            ->where('date', $data['date']);

        if (isset($data['week_number']) && $data['week_number'] !== null && $data['week_number'] !== '') {
            $groupQuery->where('week_number', $data['week_number']);
        } else {
            $groupQuery->whereNull('week_number');
        }

        if ($excludeId) {
            $groupQuery->where('id', '!=', $excludeId);
        }

        if ($groupQuery->exists()) {
            throw new \Exception('Конфлікт розкладу: група вже має заняття в цей час');
        }

        // Check teacher conflict
        $teacherQuery = Schedule::where('teacher_id', $data['teacher_id'])
            ->where('day_of_week', $dayOfWeek)
            ->where('time_slot', $data['time_slot'])
            ->where('date', $data['date']);

        if (isset($data['week_number']) && $data['week_number'] !== null && $data['week_number'] !== '') {
            $teacherQuery->where('week_number', $data['week_number']);
        } else {
            $teacherQuery->whereNull('week_number');
        }

        if ($excludeId) {
            $teacherQuery->where('id', '!=', $excludeId);
        }

        if ($teacherQuery->exists()) {
            throw new \Exception('Конфлікт розкладу: викладач вже зайнятий в цей час');
        }
    }
}
