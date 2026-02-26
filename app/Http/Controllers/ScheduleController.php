<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Course;
use App\Models\Group;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleVersion;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Show main schedule page (for authenticated users with tenant context)
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
     * Get schedule for group within a date range (uses ScheduleVersion + ScheduleAssignment)
     */
    public function getSchedule(int $groupId, string $startDate, string $endDate): JsonResponse
    {
        // Find the published schedule version for this group's tenant
        $group = Group::findOrFail($groupId);

        $publishedVersion = ScheduleVersion::where('tenant_id', $group->tenant_id)
            ->where('status', 'published')
            ->latest('published_at')
            ->first();

        if (!$publishedVersion) {
            return response()->json([
                'schedule' => [],
                'time_slots' => [],
                'date_range' => [],
                'message' => 'Немає опублікованого розкладу',
            ]);
        }

        // Get assignments for this group through activities
        $assignments = ScheduleAssignment::where('schedule_version_id', $publishedVersion->id)
            ->whereHas('activity', function ($query) use ($groupId) {
                $query->whereHas('groups', function ($q) use ($groupId) {
                    $q->where('groups.id', $groupId);
                });
            })
            ->with(['activity.subject', 'activity.teachers', 'room'])
            ->get();

        // Get time slots from calendar
        $timeSlots = TimeSlot::where('calendar_id', $publishedVersion->calendar_id)
            ->where('enabled', true)
            ->orderBy('slot_index')
            ->get();

        // Build schedule matrix (day_of_week × slot_index)
        $scheduleMatrix = [];

        $startDateObj = \Carbon\Carbon::parse($startDate);
        $endDateObj = \Carbon\Carbon::parse($endDate);
        $dateRange = [];

        $dayNames = [
            1 => 'Понеділок', 2 => 'Вівторок', 3 => 'Середа',
            4 => 'Четвер', 5 => "П'ятниця", 6 => 'Субота', 7 => 'Неділя',
        ];

        for ($date = $startDateObj->copy(); $date->lte($endDateObj); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek === 0 ? 7 : $date->dayOfWeek;
            $dateStr = $date->format('Y-m-d');

            $dateRange[] = [
                'date' => $dateStr,
                'formatted' => $date->format('d.m.Y'),
                'day_name' => $dayNames[$dayOfWeek] ?? 'Невідомо',
                'day_of_week' => $dayOfWeek,
            ];

            foreach ($timeSlots as $slot) {
                $scheduleMatrix[$dateStr][$slot->slot_index] = null;
            }

            // Fill with assignments
            foreach ($assignments as $assignment) {
                if ($assignment->day_of_week === $dayOfWeek) {
                    $activity = $assignment->activity;
                    $teachers = $activity->teachers->pluck('name')->join(', ');

                    $scheduleMatrix[$dateStr][$assignment->slot_index] = [
                        'id' => $assignment->id,
                        'subject' => $activity->subject->name ?? $activity->title,
                        'subject_type' => $activity->activity_type,
                        'teacher' => $teachers,
                        'classroom' => $assignment->room->code ?? '',
                        'room_title' => $assignment->room->title ?? '',
                        'date' => $dateStr,
                        'formatted_date' => $date->format('d.m.Y'),
                        'parity' => $assignment->parity,
                    ];
                }
            }
        }

        // Format time slots for frontend
        $formattedSlots = [];
        foreach ($timeSlots as $slot) {
            $formattedSlots[$slot->slot_index] = $slot->start_time . '-' . $slot->end_time;
        }

        return response()->json([
            'schedule' => $scheduleMatrix,
            'time_slots' => $formattedSlots,
            'date_range' => $dateRange,
            'version' => [
                'id' => $publishedVersion->id,
                'name' => $publishedVersion->name,
                'published_at' => $publishedVersion->published_at?->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Get current week date range
     */
    public function getCurrentWeekRange(): JsonResponse
    {
        $today = new \DateTime();
        $dayOfWeek = $today->format('N');

        $startOfWeek = clone $today;
        $startOfWeek->modify('-' . ($dayOfWeek - 1) . ' days');

        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days');

        return response()->json([
            'start_date' => $startOfWeek->format('Y-m-d'),
            'end_date' => $endOfWeek->format('Y-m-d'),
            'start_date_formatted' => $startOfWeek->format('d.m.Y'),
            'end_date_formatted' => $endOfWeek->format('d.m.Y'),
            'label' => "Поточний тиждень ({$startOfWeek->format('d.m')} - {$endOfWeek->format('d.m.Y')})",
        ]);
    }

    /**
     * Get list of weeks
     */
    public function getWeeks(): JsonResponse
    {
        $weeks = [];
        $currentYear = date('Y');
        $startDate = new \DateTime("{$currentYear}-01-01");

        while ($startDate->format('N') != 1) {
            $startDate->add(new \DateInterval('P1D'));
        }

        for ($week = 1; $week <= 52; $week++) {
            $endDate = clone $startDate;
            $endDate->add(new \DateInterval('P6D'));

            $weeks[] = [
                'number' => $week,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'start_date_formatted' => $startDate->format('d.m.Y'),
                'end_date_formatted' => $endDate->format('d.m.Y'),
                'label' => "Тиждень {$week} ({$startDate->format('d.m')} - {$endDate->format('d.m.Y')})",
            ];

            $startDate->add(new \DateInterval('P7D'));
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
        $subjects = Subject::with('teachers')->get(['id', 'name']);
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
}
