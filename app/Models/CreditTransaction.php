<?php

namespace App\Models;

use Database\Factories\CreditTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CreditTransaction extends Model
{
    /** @use HasFactory<CreditTransactionFactory> */
    use HasFactory;

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_HOLD = 'hold';

    public const TYPE_SETTLEMENT = 'settlement';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'uuid',
        'user_id',
        'generation_id',
        'type',
        'amount_micros',
        'balance_after_micros',
        'reference',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount_micros' => 'integer',
            'balance_after_micros' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CreditTransaction $transaction) {
            $transaction->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    public function isCredit(): bool
    {
        return $this->amount_micros > 0;
    }
}
