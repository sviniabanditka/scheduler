<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'subject_id',
        'teacher_id',
        'day_of_week',
        'time_slot',
        'week_number',
        'date',
        'classroom',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Constants for days of the week
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;
    public const SUNDAY = 7;

    public const DAYS_OF_WEEK = [
        self::MONDAY => 'Понеділок',
        self::TUESDAY => 'Вівторок',
        self::WEDNESDAY => 'Середа',
        self::THURSDAY => 'Четвер',
        self::FRIDAY => 'П\'ятниця',
        self::SATURDAY => 'Субота',
        self::SUNDAY => 'Неділя',
    ];

    // Constants for time slots
    public const TIME_SLOTS = [
        '08:00-09:30' => '08:00-09:30',
        '09:45-11:15' => '09:45-11:15',
        '11:30-13:00' => '11:30-13:00',
        '13:15-14:45' => '13:15-14:45',
        '15:00-16:30' => '15:00-16:30',
        '16:45-18:15' => '16:45-18:15',
        '18:30-20:00' => '18:30-20:00',
    ];

    /**
     * Get the group that this schedule belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the subject for this schedule.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the teacher for this schedule.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the day of week label.
     */
    public function getDayOfWeekLabelAttribute(): string
    {
        return self::DAYS_OF_WEEK[$this->day_of_week] ?? 'Невідомо';
    }

    /**
     * Validation rules for schedule.
     */
    public static function rules($id = null): array
    {
        return [
            'group_id' => 'required|exists:groups,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'time_slot' => 'required|string|max:20',
            'week_number' => 'nullable|integer|min:1|max:52',
            'date' => 'required|date',
            'classroom' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get the formatted date for this schedule.
     */
    public function getFormattedDateAttribute(): string
    {
        /** @var Carbon $date */
        $date = $this->date;
        return $date->format('d.m.Y');
    }

    /**
     * Check if the schedule is active for a given date.
     */
    public function isActiveForDate(\DateTime $date): bool
    {
        /** @var Carbon $scheduleDate */
        $scheduleDate = $this->date;
        return $scheduleDate->format('Y-m-d') === $date->format('Y-m-d');
    }
}
