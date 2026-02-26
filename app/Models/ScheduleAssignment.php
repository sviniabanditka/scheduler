<?php

namespace App\Models;

use App\Models\Traits\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleAssignment extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'schedule_version_id',
        'activity_id',
        'day_of_week',
        'slot_index',
        'parity',
        'room_id',
        'locked',
        'source',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'slot_index' => 'integer',
        'locked' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scheduleVersion(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class, 'schedule_version_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
