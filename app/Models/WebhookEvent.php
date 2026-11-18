<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    protected $fillable = [
        'provider_event_id',
        'type',
        'status',
        'generation_id',
        'provider_generation_id',
        'signature_valid',
        'processed',
        'ignored',
        'occurred_at',
        'payload',
        'headers',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'processed' => 'boolean',
            'ignored' => 'boolean',
            'occurred_at' => 'datetime',
            'headers' => 'array',
        ];
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    public function payloadArray(): array
    {
        $decoded = json_decode((string) $this->payload, true);

        return is_array($decoded) ? $decoded : [];
    }
}
