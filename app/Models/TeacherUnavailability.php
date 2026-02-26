<?php

namespace App\Models;

use App\Models\Traits\TenantScope;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherUnavailability extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'teacher_id',
        'calendar_id',
        'day_of_week',
        'slot_index',
        'parity',
        'reason',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'slot_index' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }
}
