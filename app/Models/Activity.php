<?php

namespace App\Models;

use App\Models\Traits\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'subject_id',
        'title',
        'activity_type',
        'duration_slots',
        'required_slots_per_period',
        'calendar_id',
        'notes',
    ];

    protected $casts = [
        'duration_slots' => 'integer',
        'required_slots_per_period' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'activity_groups')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'activity_teachers')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class);
    }
}
