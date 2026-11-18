<?php

namespace App\Http\Resources;

use App\Models\CreditTransaction;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CreditTransaction */
class CreditTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'amount_micros' => (int) $this->amount_micros,
            'credits' => Money::microsToCredits(abs((int) $this->amount_micros), 2),
            'balance_after_micros' => (int) $this->balance_after_micros,
            'description' => $this->description,
            'package' => $this->meta['package'] ?? null,
            'generation_uuid' => $this->whenLoaded('generation', fn () => $this->generation?->uuid),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
