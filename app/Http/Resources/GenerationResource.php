<?php

namespace App\Http\Resources;

use App\Models\Generation;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Generation */
class GenerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $estimatedProvider = (int) $this->estimated_provider_cost_micros;
        $estimatedTotal = (int) $this->estimated_user_charge_micros;
        $actualProvider = $this->actual_provider_cost_micros;
        $actualTotal = $this->actual_user_charge_micros;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'prompt' => $this->prompt,
            'size' => $this->size,
            'width' => (int) $this->width,
            'height' => (int) $this->height,
            'quality' => $this->quality,
            'status' => $this->status,
            'image_url' => $this->image_url,
            'thumbnail_url' => $this->thumbnail_url,
            'reference_images' => $this->reference_images ?? [],
            'provider_generation_id' => $this->provider_generation_id,
            'estimated' => [
                'provider_micros' => $estimatedProvider,
                'markup_micros' => max(0, $estimatedTotal - $estimatedProvider),
                'total_micros' => $estimatedTotal,
                'markup_basis_points' => (int) $this->markup_basis_points,
            ],
            'actual' => $this->isSettled() ? [
                'provider_micros' => (int) ($actualProvider ?? 0),
                'markup_micros' => max(0, (int) $actualTotal - (int) $actualProvider),
                'total_micros' => (int) ($actualTotal ?? 0),
            ] : null,
            'charged_micros' => (int) $this->charged_micros,
            'charged_credits' => Money::microsToCredits((int) $this->charged_micros, 2),
            'usage' => $this->usage,
            'error' => $this->error_message ? [
                'code' => $this->error_code,
                'message' => $this->error_message,
            ] : null,
            'can_delete' => $this->isTerminal(),
            'is_terminal' => $this->isTerminal(),
            'created_at' => $this->created_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'settled_at' => $this->settled_at?->toIso8601String(),
        ];
    }
}
