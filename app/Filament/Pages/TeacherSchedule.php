<?php

namespace App\Filament\Pages;

use App\Models\Calendar;
use App\Models\RescheduleRequest;
use App\Models\Room;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleVersion;
use App\Models\TeacherPreferenceRule;
use App\Models\TimeSlot;
use App\Services\RescheduleService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TeacherSchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.teacher-schedule';

    protected static ?string $navigationLabel = 'Мій розклад';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Мій кабінет';

    protected static ?string $title = 'Мій розклад';

    public ?string $startDate = null;
    public ?string $endDate = null;

    // Reschedule modal
    public bool $showRescheduleModal = false;
    public ?int $rescheduleAssignmentId = null;
    public ?int $proposedDayOfWeek = null;
    public ?int $proposedSlotIndex = null;
    public string $proposedParity = 'both';
    public ?int $proposedRoomId = null;
    public string $teacherComment = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->isTeacher() && $user->teacher_id;
    }

    public function mount(): void
    {
        $now = Carbon::now();
        $this->startDate = $now->startOfWeek()->format('Y-m-d');
        $this->endDate = $now->endOfWeek()->format('Y-m-d');
    }

    public function getPublishedVersionProperty(): ?ScheduleVersion
    {
        return ScheduleVersion::where('status', 'published')
            ->latest('published_at')
            ->first();
    }

    public function getCalendarProperty(): ?Calendar
    {
        return $this->publishedVersion?->calendar;
    }

    public function getTimeSlotsProperty()
    {
        if (!$this->calendar) return collect();
        return TimeSlot::where('calendar_id', $this->calendar->id)
            ->where('enabled', true)
            ->orderBy('slot_index')
            ->get()
            ->unique('slot_index');
    }

    public function getRoomsProperty()
    {
        return Room::where('active', true)->orderBy('code')->get();
    }

    public function getScheduleDataProperty(): array
    {
        $version = $this->publishedVersion;
        if (!$version || !$this->startDate || !$this->endDate) return ['matrix' => [], 'dateRange' => []];

        $teacherId = auth()->user()->teacher_id;
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        // Build date range
        $dateRange = [];
        $dayNames = TeacherPreferenceRule::DAY_NAMES;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dow = $date->dayOfWeekIso;
            if ($dow > 6) continue;
            $dateRange[] = [
                'date' => $date->format('Y-m-d'),
                'day_of_week' => $dow,
                'day_name' => $dayNames[$dow] ?? $dow,
                'formatted' => $date->format('d.m'),
            ];
        }

        // Get teacher's assignments
        $assignments = ScheduleAssignment::with(['activity.subject', 'activity.teachers', 'activity.groups', 'room'])
            ->where('schedule_version_id', $version->id)
            ->whereHas('activity.teachers', fn ($q) => $q->where('teachers.id', $teacherId))
            ->get();

        // Build matrix
        $matrix = [];
        foreach ($dateRange as $day) {
            $dayAssignments = $assignments->filter(fn ($a) => $a->day_of_week === $day['day_of_week']);
            foreach ($dayAssignments as $a) {
                $activity = $a->activity;
                $matrix[$day['date']][$a->slot_index] = [
                    'id' => $a->id,
                    'subject' => $activity?->subject?->name ?? $activity?->title ?? '—',
                    'type' => $activity?->activity_type ?? 'default',
                    'groups' => $activity?->groups?->pluck('name')->join(', ') ?? '',
                    'room' => $a->room?->code ?? '',
                    'parity' => $a->parity,
                    'locked' => $a->locked,
                ];
            }
        }

        return ['matrix' => $matrix, 'dateRange' => $dateRange];
    }

    public function getMyRequestsProperty()
    {
        $teacherId = auth()->user()->teacher_id;
        return RescheduleRequest::with(['assignment.activity.subject', 'reviewer'])
            ->where('teacher_id', $teacherId)
            ->latest()
            ->take(10)
            ->get();
    }

    public function openRescheduleModal(int $assignmentId): void
    {
        $assignment = ScheduleAssignment::find($assignmentId);
        if (!$assignment) return;

        $this->rescheduleAssignmentId = $assignmentId;
        $this->proposedDayOfWeek = $assignment->day_of_week;
        $this->proposedSlotIndex = $assignment->slot_index;
        $this->proposedParity = $assignment->parity;
        $this->proposedRoomId = $assignment->room_id;
        $this->teacherComment = '';
        $this->showRescheduleModal = true;
    }

    public function closeRescheduleModal(): void
    {
        $this->showRescheduleModal = false;
        $this->rescheduleAssignmentId = null;
    }

    public function submitReschedule(): void
    {
        if (!$this->rescheduleAssignmentId || !$this->proposedDayOfWeek || !$this->proposedSlotIndex) {
            Notification::make()->title('Заповніть всі поля')->warning()->send();
            return;
        }

        $user = auth()->user();

        $request = RescheduleRequest::create([
            'tenant_id' => $user->tenant_id,
            'teacher_id' => $user->teacher_id,
            'assignment_id' => $this->rescheduleAssignmentId,
            'proposed_day_of_week' => $this->proposedDayOfWeek,
            'proposed_slot_index' => $this->proposedSlotIndex,
            'proposed_parity' => $this->proposedParity,
            'proposed_room_id' => $this->proposedRoomId,
            'status' => 'pending',
            'teacher_comment' => $this->teacherComment,
        ]);

        // Validate conflicts
        $service = app(RescheduleService::class);
        $conflicts = $service->validateProposal($request);

        if (!empty($conflicts)) {
            Notification::make()
                ->title('Увага: можливі конфлікти')
                ->body(implode('. ', $conflicts))
                ->warning()
                ->send();
        }

        $this->closeRescheduleModal();

        Notification::make()
            ->title('Заявку на перенос надіслано')
            ->body('Адміністратор розгляне вашу заявку')
            ->success()
            ->send();
    }
}
