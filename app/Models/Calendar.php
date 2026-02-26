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
        'days_per_week',
        'slots_per_day',
        'slot_duration_minutes',
        'break_duration_minutes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'parity_enabled' => 'boolean',
        'days_per_week' => 'integer',
        'slots_per_day' => 'integer',
        'slot_duration_minutes' => 'integer',
        'break_duration_minutes' => 'integer',
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
}
