<?php

namespace App\Models;

use App\Models\Traits\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory, TenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'teacher_id',
        'type',
    ];

    public const TYPE_LECTURE = 'lecture';
    public const TYPE_PRACTICE = 'practice';
    public const TYPE_LAB = 'lab';
    public const TYPE_SEMINAR = 'seminar';
    public const TYPE_PC = 'pc';

    public const TYPES = [
        self::TYPE_LECTURE => 'Лекція',
        self::TYPE_PRACTICE => 'Практика',
        self::TYPE_LAB => 'Лабораторна',
        self::TYPE_SEMINAR => 'Семінар',
        self::TYPE_PC => 'Комп\'ютерний клас',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public static function rules($id = null): array
    {
        $tenantId = app('tenant')?->id;
        
        return [
            'name' => 'required|string|max:255',
            'teacher_id' => 'required|exists:teachers,id',
            'type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
        ];
    }
}
