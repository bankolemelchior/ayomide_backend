<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'price_m2',
        'promo_price_m2',
        'promo_label',
        'dimension',
        'type',
        'finition',
        'thickness',
        'usage',
        'epaisseur',
        'images',
        'popular',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_m2' => 'integer',
            'promo_price_m2' => 'integer',
            'images' => 'array',
            'popular' => 'boolean',
        ];
    }
}
