<?php

namespace App\Models;

use App\Models\Traits\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'size',
        'semester',
        'program',
        'active',
        'course_id',
    ];

    protected $casts = [
        'size' => 'integer',
        'semester' => 'integer',
        'active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_groups')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public static function rules($id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'size' => 'required|integer|min:1',
            'semester' => 'nullable|integer|min:1|max:12',
            'program' => 'nullable|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'active' => 'boolean',
        ];
    }
}
