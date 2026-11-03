<?php

namespace App\Models;

use App\Models\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use HasUUID;

    protected $guarded = [];

    protected $casts = [
        'date' => 'immutable_date',
        'is_recurring' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, ?int $companyId)
    {
        return $query->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', $companyId));
    }
}
