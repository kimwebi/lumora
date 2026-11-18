<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderUsageRecord extends Model
{
    protected $fillable = [
        'provider_usage_id',
        'provider_generation_id',
        'generation_id',
        'input_tokens',
        'output_tokens',
        'image_tokens',
        'total_tokens',
        'provider_cost_micros',
        'occurred_at',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'image_tokens' => 'integer',
            'total_tokens' => 'integer',
            'provider_cost_micros' => 'integer',
            'occurred_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }
}
