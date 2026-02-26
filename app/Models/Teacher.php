<?php

namespace App\Models;

use App\Models\Traits\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\Rule;

class Teacher extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_teachers')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(TeacherUnavailability::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function preferenceRules(): HasMany
    {
        return $this->hasMany(TeacherPreferenceRule::class);
    }

    public function rescheduleRequests(): HasMany
    {
        return $this->hasMany(RescheduleRequest::class);
    }

    public static function rules($id = null): array
    {
        $tenantId = app('tenant')?->id;
        
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('teachers')->where('tenant_id', $tenantId)->ignore($id),
            ],
            'phone' => 'nullable|string|max:20',
        ];
    }
}
