<?php

namespace App\Models;

use App\Models\Traits\TenantScope;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSlot extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'calendar_id',
        'day_of_week',
        'slot_index',
        'start_time',
        'end_time',
        'parity',
        'enabled',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'slot_index' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }
}
