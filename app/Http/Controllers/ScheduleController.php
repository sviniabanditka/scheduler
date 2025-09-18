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
     * Get schedule for group and week
     */
    public function getSchedule(int $groupId, int $week): JsonResponse
    {
        $schedules = Schedule::with(['subject', 'teacher', 'group.course'])
            ->where('group_id', $groupId)
            ->where('week_number', $week)
            ->get();

        // Create schedule matrix (day × time)
        $scheduleMatrix = [];
        $timeSlots = array_keys(Schedule::TIME_SLOTS);
        $daysOfWeek = array_keys(Schedule::DAYS_OF_WEEK);

        // Initialize empty matrix
        foreach ($daysOfWeek as $day) {
            foreach ($timeSlots as $time) {
                $scheduleMatrix[$day][$time] = null;
            }
        }

        // Fill matrix with data from database
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
     * Get list of weeks with dates
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
                'start_date' => $startDate->format('d.m.Y'),
                'end_date' => $endDate->format('d.m.Y'),
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
                'day_of_week' => 'required|integer|min:1|max:7',
                'time_slot' => 'required|string',
                'week_number' => 'nullable|integer|min:1|max:52',
                'classroom' => 'nullable|string|max:50',
            ]);

            // Check for conflicts
            $this->validateScheduleConflicts($request->all());

            $schedule = Schedule::create($request->all());
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
                'day_of_week' => 'required|integer|min:1|max:7',
                'time_slot' => 'required|string',
                'week_number' => 'nullable|integer|min:1|max:52',
                'classroom' => 'nullable|string|max:50',
            ]);

            // Check for conflicts (excluding current lesson)
            $this->validateScheduleConflicts($request->all(), $id);

            $schedule->update($request->all());
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
        // Check group conflict
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
            throw new \Exception('Конфлікт розкладу: група вже має заняття в цей час');
        }

        // Check teacher conflict
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
            throw new \Exception('Конфлікт розкладу: викладач вже зайнятий в цей час');
        }
    }
}
