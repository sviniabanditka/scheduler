<?php

namespace App\Models;

use App\Models\Traits\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'number',
    ];

    protected $casts = [
        'number' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public static function rules($id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'number' => 'required|integer|min:1|max:12',
        ];
    }
}
