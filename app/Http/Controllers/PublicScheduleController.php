<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Course;
use App\Models\Group;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleVersion;
use App\Models\Tenant;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PublicScheduleController extends Controller
{
    /**
     * Show public schedule page for a tenant
     */
    public function show(string $slug)
    {
        $tenant = Tenant::where('public_slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $courses = Course::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderBy('number')
            ->get();

        // Get published version's calendar for date limits
        $publishedVersion = ScheduleVersion::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->latest('published_at')
            ->first();

        $calendar = null;
        if ($publishedVersion) {
            $calendar = Calendar::withoutGlobalScopes()
                ->find($publishedVersion->calendar_id);
        }

        return view('public-schedule', [
            'tenant' => $tenant,
            'courses' => $courses,
            'slug' => $slug,
            'calendar' => $calendar,
        ]);
    }

    /**
     * API: Get groups for a course (public)
     */
    public function getGroups(string $slug, int $courseId): JsonResponse
    {
        $tenant = Tenant::where('public_slug', $slug)->where('is_active', true)->firstOrFail();

        $groups = Group::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('course_id', $courseId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($groups);
    }

    /**
     * API: Get schedule data for a group (public, published version only)
     */
    public function getScheduleData(string $slug, int $groupId, string $startDate, string $endDate): JsonResponse
    {
        $tenant = Tenant::where('public_slug', $slug)->where('is_active', true)->firstOrFail();

        // Only published version
        $publishedVersion = ScheduleVersion::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->latest('published_at')
            ->first();

        if (!$publishedVersion) {
            return response()->json([
                'schedule' => [],
                'time_slots' => [],
                'date_range' => [],
                'message' => 'Розклад ще не опубліковано',
            ]);
        }

        // Get calendar for date range validation
        $calendar = Calendar::withoutGlobalScopes()
            ->find($publishedVersion->calendar_id);

        if (!$calendar) {
            return response()->json([
                'schedule' => [],
                'time_slots' => [],
                'date_range' => [],
                'message' => 'Календар не знайдено',
            ]);
        }

        // Parse and clamp dates to calendar range
        $requestStart = Carbon::parse($startDate);
        $requestEnd = Carbon::parse($endDate);
        $calendarStart = $calendar->start_date;
        $calendarEnd = $calendar->end_date;

        // If the entire requested range is outside the calendar, return empty
        if ($requestStart->gt($calendarEnd) || $requestEnd->lt($calendarStart)) {
            return response()->json([
                'schedule' => [],
                'time_slots' => [],
                'date_range' => [],
                'message' => "Обраний період за межами календаря ({$calendarStart->format('d.m.Y')} — {$calendarEnd->format('d.m.Y')})",
                'calendar_range' => [
                    'start' => $calendarStart->format('Y-m-d'),
                    'end' => $calendarEnd->format('Y-m-d'),
                ],
            ]);
        }

        // Clamp to calendar bounds
        $effectiveStart = $requestStart->lt($calendarStart) ? $calendarStart->copy() : $requestStart;
        $effectiveEnd = $requestEnd->gt($calendarEnd) ? $calendarEnd->copy() : $requestEnd;

        $assignments = ScheduleAssignment::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('schedule_version_id', $publishedVersion->id)
            ->whereHas('activity', function ($query) use ($groupId) {
                $query->whereHas('groups', function ($q) use ($groupId) {
                    $q->where('groups.id', $groupId);
                });
            })
            ->with(['activity.subject', 'activity.teachers', 'room'])
            ->get();

        $timeSlots = TimeSlot::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('calendar_id', $publishedVersion->calendar_id)
            ->where('enabled', true)
            ->orderBy('slot_index')
            ->get();

        // Build schedule matrix
        $scheduleMatrix = [];
        $dateRange = [];

        $dayNames = [
            1 => 'Понеділок', 2 => 'Вівторок', 3 => 'Середа',
            4 => 'Четвер', 5 => "П'ятниця", 6 => 'Субота', 7 => 'Неділя',
        ];

        for ($date = $effectiveStart->copy(); $date->lte($effectiveEnd); $date->addDay()) {
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
                        'parity' => $assignment->parity,
                    ];
                }
            }
        }

        $formattedSlots = [];
        foreach ($timeSlots as $slot) {
            $formattedSlots[$slot->slot_index] = $slot->start_time . '-' . $slot->end_time;
        }

        return response()->json([
            'schedule' => $scheduleMatrix,
            'time_slots' => $formattedSlots,
            'date_range' => $dateRange,
            'version' => [
                'name' => $publishedVersion->name,
                'published_at' => $publishedVersion->published_at?->format('d.m.Y H:i'),
            ],
            'calendar_range' => [
                'start' => $calendarStart->format('Y-m-d'),
                'end' => $calendarEnd->format('Y-m-d'),
            ],
        ]);
    }
}
