<?php

namespace App\Models;

use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const TERMINAL_STATUSES = [self::STATUS_SUCCEEDED, self::STATUS_FAILED];

    protected $fillable = [
        'uuid',
        'user_id',
        'prompt',
        'size',
        'width',
        'height',
        'quality',
        'reference_images',
        'status',
        'provider',
        'provider_generation_id',
        'provider_status',
        'error_code',
        'error_message',
        'image_url',
        'thumbnail_url',
        'estimated_provider_cost_micros',
        'estimated_user_charge_micros',
        'actual_provider_cost_micros',
        'actual_user_charge_micros',
        'markup_basis_points',
        'charged_micros',
        'usage',
        'meta',
        'submitted_at',
        'last_provider_event_at',
        'settled_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'reference_images' => 'array',
            'usage' => 'array',
            'meta' => 'array',
            'width' => 'integer',
            'height' => 'integer',
            'estimated_provider_cost_micros' => 'integer',
            'estimated_user_charge_micros' => 'integer',
            'actual_provider_cost_micros' => 'integer',
            'actual_user_charge_micros' => 'integer',
            'markup_basis_points' => 'integer',
            'charged_micros' => 'integer',
            'submitted_at' => 'datetime',
            'last_provider_event_at' => 'datetime',
            'settled_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Generation $generation) {
            $generation->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(CreditTransaction::class)->where('type', CreditTransaction::TYPE_SETTLEMENT);
    }

    public function image(): HasOne
    {
        return $this->hasOne(GenerationImage::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function scopeSettled(Builder $query): Builder
    {
        return $query->whereNotNull('settled_at');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function isSucceeded(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true);
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }

    public function markupMicros(): int
    {
        return max(0, ($this->actual_user_charge_micros ?? $this->estimated_user_charge_micros)
            - ($this->actual_provider_cost_micros ?? $this->estimated_provider_cost_micros));
    }
}
