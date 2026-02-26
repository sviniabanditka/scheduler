<?php

namespace App\Models;

use App\Models\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherPreferenceRule extends Model
{
    use HasFactory, TenantScope;

    const RULE_UNAVAILABLE_DAY = 'unavailable_day';
    const RULE_UNAVAILABLE_SLOT = 'unavailable_slot';
    const RULE_PREFERRED_SLOT = 'preferred_slot';
    const RULE_MIN_START_SLOT = 'min_start_slot';
    const RULE_MAX_END_SLOT = 'max_end_slot';
    const RULE_MAX_HOURS_PER_DAY = 'max_hours_per_day';

    const RULE_TYPES = [
        self::RULE_UNAVAILABLE_DAY => 'Недоступний день',
        self::RULE_UNAVAILABLE_SLOT => 'Недоступний слот',
        self::RULE_PREFERRED_SLOT => 'Бажаний слот',
        self::RULE_MIN_START_SLOT => 'Мінімальна початкова пара',
        self::RULE_MAX_END_SLOT => 'Максимальна кінцева пара',
        self::RULE_MAX_HOURS_PER_DAY => 'Макс. пар на день',
    ];

    const DAY_NAMES = [
        1 => 'Понеділок',
        2 => 'Вівторок',
        3 => 'Середа',
        4 => 'Четвер',
        5 => "П'ятниця",
        6 => 'Субота',
    ];

    protected $fillable = [
        'tenant_id',
        'teacher_id',
        'rule_type',
        'params',
        'priority',
        'weight',
        'is_active',
        'comment',
    ];

    protected $casts = [
        'params' => 'array',
        'priority' => 'integer',
        'weight' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get human-readable description of the rule
     */
    public function getDescriptionAttribute(): string
    {
        $params = $this->params ?? [];
        $dayName = isset($params['day_of_week']) ? (self::DAY_NAMES[$params['day_of_week']] ?? $params['day_of_week']) : '';

        return match ($this->rule_type) {
            self::RULE_UNAVAILABLE_DAY => "Не працювати: {$dayName}",
            self::RULE_UNAVAILABLE_SLOT => "Не працювати: {$dayName}, пара {$params['slot_index']}",
            self::RULE_PREFERRED_SLOT => "Бажано: {$dayName}, пара {$params['slot_index']}",
            self::RULE_MIN_START_SLOT => $dayName
                ? "Починати не раніше пари {$params['min_slot']}: {$dayName}"
                : "Починати не раніше пари {$params['min_slot']}",
            self::RULE_MAX_END_SLOT => $dayName
                ? "Закінчувати не пізніше пари {$params['max_slot']}: {$dayName}"
                : "Закінчувати не пізніше пари {$params['max_slot']}",
            self::RULE_MAX_HOURS_PER_DAY => "Макс. {$params['max_hours']} пар на день",
            default => $this->rule_type,
        };
    }
}
