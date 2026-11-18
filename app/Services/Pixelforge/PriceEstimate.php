<?php

namespace App\Services\Pixelforge;

use App\Support\Money;

final readonly class PriceEstimate
{
    public function __construct(
        public int $providerMicros,
        public int $markupMicros,
        public int $totalMicros,
        public int $markupBasisPoints,
        public int $images = 1,
        public string $size = '1024x1024',
        public string $quality = 'standard',
    ) {}

    public function toArray(): array
    {
        return [
            'provider_micros' => $this->providerMicros,
            'markup_micros' => $this->markupMicros,
            'total_micros' => $this->totalMicros,
            'markup_basis_points' => $this->markupBasisPoints,
            'provider_usd' => Money::microsToUsd($this->providerMicros, 6),
            'markup_usd' => Money::microsToUsd($this->markupMicros, 6),
            'total_usd' => Money::microsToUsd($this->totalMicros, 6),
            'images' => $this->images,
            'size' => $this->size,
            'quality' => $this->quality,
        ];
    }
}
