<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whatsapp_number',
        'name',
        'address',
        'latitude',
        'longitude',
        'referral_code',
        'balance',
        'is_active'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'balance' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'entity_id')
            ->where('entity_type', 'customer');
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class, 'entity_id')
            ->where('entity_type', 'customer');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedWhatsappNumberAttribute(): string
    {
        return $this->whatsapp_number;
    }

    public function getFullAddressAttribute(): string
    {
        return $this->address ?? 'Alamat tidak tersedia';
    }
} 