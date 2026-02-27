<?php

namespace App\Models;

use App\Models\Traits\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleVersion extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'calendar_id',
        'name',
        'status',
        'created_by',
        'parent_version_id',
        'version_number',
        'random_seed',
        'generation_params',
        'published_at',
        'generation_started_at',
        'generation_finished_at',
    ];

    protected $casts = [
        'generation_params' => 'array',
        'published_at' => 'datetime',
        'generation_started_at' => 'datetime',
        'generation_finished_at' => 'datetime',
        'version_number' => 'integer',
        'random_seed' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class, 'parent_version_id');
    }

    public function childVersions(): HasMany
    {
        return $this->hasMany(ScheduleVersion::class, 'parent_version_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class, 'schedule_version_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }
}
