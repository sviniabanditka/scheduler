<?php

namespace App\Models;

use App\Models\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescheduleRequest extends Model
{
    use HasFactory, TenantScope;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id',
        'teacher_id',
        'assignment_id',
        'proposed_day_of_week',
        'proposed_slot_index',
        'proposed_parity',
        'proposed_room_id',
        'status',
        'teacher_comment',
        'admin_comment',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'proposed_day_of_week' => 'integer',
        'proposed_slot_index' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ScheduleAssignment::class, 'assignment_id');
    }

    public function proposedRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'proposed_room_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
