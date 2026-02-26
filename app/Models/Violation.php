<?php

namespace App\Models;

use App\Models\Traits\TenantScope;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Violation extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'schedule_version_id',
        'activity_id',
        'code',
        'severity',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scheduleVersion(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
