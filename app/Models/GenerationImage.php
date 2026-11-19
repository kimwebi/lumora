<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Durable copy of a finished render, used when the app does not have a
 * persistent filesystem (for example Render's free plan, whose container is
 * rebuilt on every deploy and restart).
 */
class GenerationImage extends Model
{
    protected $fillable = [
        'generation_id',
        'mime',
        'extension',
        'bytes',
        'checksum',
        'data',
    ];

    /**
     * Keep the encoded bytes out of serialised payloads.
     *
     * @var list<string>
     */
    protected $hidden = ['data'];

    protected function casts(): array
    {
        return [
            'bytes' => 'integer',
        ];
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }
}
