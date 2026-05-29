<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estimate extends Model
{
    protected $fillable = [
        'client_name', 'client_email', 'client_phone',
        'estimate_date', 'status', 'total_amount',
    ];

    protected $casts = [
        'estimate_date' => 'date',
        'total_amount'  => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class);
    }
}
