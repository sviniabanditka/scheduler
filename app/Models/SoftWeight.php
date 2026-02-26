<?php

namespace App\Models;

use App\Models\Traits\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftWeight extends Model
{
    use TenantScope;

    protected $primaryKey = 'tenant_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'w_windows',
        'w_prefs',
        'w_balance',
    ];

    protected $casts = [
        'w_windows' => 'integer',
        'w_prefs' => 'integer',
        'w_balance' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
