<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'base_price',
        'price_per_km',
        'minimum_price',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'minimum_price' => 'decimal:2',
        'is_active' => 'boolean',
        'settings' => 'array'
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function calculatePrice(float $distanceKm): float
    {
        $distancePrice = $distanceKm * $this->price_per_km;
        $totalPrice = $this->base_price + $distancePrice;
        
        return max($totalPrice, $this->minimum_price);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->base_price, 0, ',', '.');
    }
} 