<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_number',
        'name',
        'address',
        'latitude',
        'longitude',
        'vehicle_type',
        'vehicle_number',
        'service_types',
        'referral_code',
        'balance',
        'status',
        'is_online'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'balance' => 'decimal:2',
        'service_types' => 'array',
        'is_online' => 'boolean'
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'entity_id')
            ->where('entity_type', 'partner');
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class, 'entity_id')
            ->where('entity_type', 'partner');
    }

    public function getFormattedWhatsappNumberAttribute(): string
    {
        return $this->whatsapp_number;
    }

    public function getFullAddressAttribute(): string
    {
        return $this->address ?? 'Alamat tidak tersedia';
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->is_online;
    }
} 