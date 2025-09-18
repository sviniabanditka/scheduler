<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'teacher_id',
        'type',
    ];

    // Constants for subject types
    public const TYPE_LECTURE = 'lecture';
    public const TYPE_PRACTICE = 'practice';

    public const TYPES = [
        self::TYPE_LECTURE => 'Лекція',
        self::TYPE_PRACTICE => 'Практика',
    ];

    /**
     * Get the teacher that teaches this subject.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the schedules for this subject.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Validation rules for subject.
     */
    public static function rules($id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'teacher_id' => 'required|exists:teachers,id',
            'type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
        ];
    }
}
