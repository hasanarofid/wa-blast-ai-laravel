<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'partner_id',
        'service_id',
        'status',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'destination_address',
        'destination_latitude',
        'destination_longitude',
        'distance_km',
        'base_price',
        'distance_price',
        'total_price',
        'commission',
        'partner_earnings',
        'notes',
        'items',
        'scheduled_at',
        'picked_up_at',
        'completed_at'
    ];

    protected $casts = [
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'destination_latitude' => 'decimal:8',
        'destination_longitude' => 'decimal:8',
        'distance_km' => 'decimal:2',
        'base_price' => 'decimal:2',
        'distance_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'commission' => 'decimal:2',
        'partner_earnings' => 'decimal:2',
        'items' => 'array',
        'scheduled_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    public function getFormattedDistanceAttribute(): string
    {
        return number_format($this->distance_km, 1) . ' km';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
} 