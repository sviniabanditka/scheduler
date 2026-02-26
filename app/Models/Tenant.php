<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'subdomain',
        'domain',
        'public_slug',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->public_slug)) {
                $tenant->public_slug = static::generateUniqueSlug($tenant->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::where('public_slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getPublicUrl(): string
    {
        return url("/s/{$this->public_slug}");
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(Calendar::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function scheduleVersions(): HasMany
    {
        return $this->hasMany(ScheduleVersion::class);
    }

    public function softWeights(): HasOne
    {
        return $this->hasOne(SoftWeight::class);
    }
}
