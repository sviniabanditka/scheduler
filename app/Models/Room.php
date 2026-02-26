<?php

namespace App\Models;

use App\Models\Traits\TenantScope;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'code',
        'title',
        'capacity',
        'room_type',
        'features',
        'active',
    ];

    protected $casts = [
        'features' => 'array',
        'active' => 'boolean',
        'capacity' => 'integer',
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
