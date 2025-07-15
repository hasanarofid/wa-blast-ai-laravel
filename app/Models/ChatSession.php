<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_number',
        'entity_type',
        'entity_id',
        'session_id',
        'status',
        'context',
        'current_step',
        'last_activity'
    ];

    protected $casts = [
        'context' => 'array',
        'last_activity' => 'datetime'
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity' => now()]);
    }

    public function getContextValue(string $key, $default = null)
    {
        return data_get($this->context, $key, $default);
    }

    public function setContextValue(string $key, $value): void
    {
        $context = $this->context ?? [];
        $context[$key] = $value;
        $this->update(['context' => $context]);
    }
} 