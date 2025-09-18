<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
    ];

    /**
     * Get the groups for this course.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Validation rules for course.
     */
    public static function rules($id = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'number' => 'required|integer|min:1|max:4',
        ];
    }
}
