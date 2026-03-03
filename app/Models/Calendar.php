<?php

namespace App\Models;

use App\Models\Traits\TenantScope;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calendar extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'start_date',
        'end_date',
        'weeks',
        'parity_enabled',
        'first_week_parity',
        'days_per_week',
        'slots_per_day',
        'slot_duration_minutes',
        'break_duration_minutes',
        'slot_times',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'parity_enabled' => 'boolean',
        'days_per_week' => 'integer',
        'slots_per_day' => 'integer',
        'slot_duration_minutes' => 'integer',
        'break_duration_minutes' => 'integer',
        'slot_times' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function scheduleVersions(): HasMany
    {
        return $this->hasMany(ScheduleVersion::class);
    }

    /**
     * Get parity ('num' or 'den') for a given date based on calendar start and first_week_parity.
     */
    public function getParityForDate(\Carbon\Carbon $date): string
    {
        if (!$this->parity_enabled) {
            return 'both';
        }

        $startMonday = $this->start_date->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $weekNumber = (int) $startMonday->diffInWeeks($date->copy()->startOfWeek(\Carbon\Carbon::MONDAY));

        $firstParity = $this->first_week_parity ?? 'num';

        // Even weeks (0, 2, 4...) = first_week_parity, odd weeks (1, 3, 5...) = opposite
        if ($weekNumber % 2 === 0) {
            return $firstParity;
        }

        return $firstParity === 'num' ? 'den' : 'num';
    }

    /**
     * Generate default slot times based on slots_per_day, duration and break settings.
     * Returns array of [['slot_number' => N, 'start_time' => 'HH:MM', 'end_time' => 'HH:MM'], ...]
     */
    public static function generateDefaultSlotTimes(int $slotsPerDay, int $durationMinutes = 90, int $breakMinutes = 10, string $startAt = '08:30'): array
    {
        $slots = [];
        $current = \Carbon\Carbon::createFromFormat('H:i', $startAt);

        for ($i = 1; $i <= $slotsPerDay; $i++) {
            $start = $current->format('H:i');
            $current->addMinutes($durationMinutes);
            $end = $current->format('H:i');

            $slots[] = [
                'slot_number' => $i,
                'start_time' => $start,
                'end_time' => $end,
            ];

            if ($i < $slotsPerDay) {
                $current->addMinutes($breakMinutes);
            }
        }

        return $slots;
    }

    /**
     * Sync time_slots table rows from slot_times JSON for all days × parities.
     */
    public function syncTimeSlots(): void
    {
        $slotTimes = $this->slot_times;
        if (empty($slotTimes)) {
            return;
        }

        // Delete existing time slots for this calendar
        $this->timeSlots()->delete();

        $parities = $this->parity_enabled ? ['num', 'den'] : ['both'];

        for ($day = 1; $day <= $this->days_per_week; $day++) {
            foreach ($parities as $parity) {
                foreach ($slotTimes as $slot) {
                    TimeSlot::withoutGlobalScopes()->create([
                        'tenant_id' => $this->tenant_id,
                        'calendar_id' => $this->id,
                        'day_of_week' => $day,
                        'slot_index' => $slot['slot_number'],
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'parity' => $parity,
                        'enabled' => true,
                    ]);
                }
            }
        }
    }
}
