<?php

namespace App\Services\Generations;

use App\Jobs\SubmitGenerationJob;
use App\Models\Generation;
use App\Models\User;
use App\Services\Billing\CreditService;
use App\Services\Pixelforge\PricingCalculator;
use App\Support\Money;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GenerationService
{
    public function __construct(
        private readonly PricingCalculator $pricing,
        private readonly CreditService $credits,
    ) {}

    public function create(User $user, array $data): Generation
    {
        $size = (string) $data['size'];
        $quality = (string) $data['quality'];
        $references = array_values(array_filter($data['reference_images'] ?? []));

        $estimate = $this->pricing->estimate($size, $quality, 1, (string) $data['prompt'], count($references));

        $active = $user->generations()->processing()->count();

        if ($active >= (int) config('lumora.generation.max_active_per_user', 3)) {
            throw ValidationException::withMessages([
                'prompt' => 'You already have the maximum number of images in progress. Wait for one to finish before starting another.',
            ]);
        }

        [$width, $height] = $this->dimensions($size);
        $uuid = (string) Str::uuid();

        return DB::transaction(function () use ($user, $data, $size, $quality, $references, $estimate, $width, $height, $uuid) {
            $lockedUser = $this->credits->lockUser($user->id);

            if ($lockedUser->credit_balance_micros < $estimate->totalMicros) {
                throw ValidationException::withMessages([
                    'credits' => 'You need '.Money::format($estimate->totalMicros).' for this image, but your available balance is '.Money::format($lockedUser->credit_balance_micros).'. Top up your credits to continue.',
                ]);
            }

            $generation = Generation::query()->create([
                'uuid' => $uuid,
                'user_id' => $lockedUser->id,
                'prompt' => trim((string) $data['prompt']),
                'size' => $size,
                'width' => $width,
                'height' => $height,
                'quality' => $quality,
                'reference_images' => $references ?: null,
                'status' => Generation::STATUS_PENDING,
                'provider' => (string) config('lumora.provider.name'),
                'estimated_provider_cost_micros' => $estimate->providerMicros,
                'estimated_user_charge_micros' => $estimate->totalMicros,
                'markup_basis_points' => $estimate->markupBasisPoints,
                'meta' => [
                    'generation_uuid' => $uuid,
                    'requested_size' => Arr::get($data, 'size'),
                    'remove_captions' => (bool) ($data['remove_captions'] ?? false),
                ],
            ]);

            $this->credits->holdForGeneration($lockedUser, $generation);

            return $generation;
        });
    }

    public function dispatch(Generation $generation): void
    {
        $job = new SubmitGenerationJob($generation->id);

        if ((bool) config('lumora.generation.after_response', false)) {
            dispatch($job)->afterResponse();
        } else {
            dispatch($job);
        }
    }

    public function statusPayload(Generation $generation): array
    {
        return [
            'uuid' => $generation->uuid,
            'status' => $generation->status,
            'is_terminal' => $generation->isTerminal(),
            'image_url' => $generation->image_url,
            'error_code' => $generation->error_code,
            'error_message' => $generation->error_message,
            'estimated_user_charge_micros' => $generation->estimated_user_charge_micros,
            'actual_user_charge_micros' => $generation->actual_user_charge_micros,
            'completed_at' => $generation->completed_at?->toIso8601String(),
            'updated_at' => $generation->updated_at?->toIso8601String(),
        ];
    }

    public function expireStale(): int
    {
        $cutoff = now()->subMinutes((int) config('lumora.generation.stale_after_minutes', 20));
        $expired = 0;

        Generation::query()
            ->processing()
            ->where('updated_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(50, function ($generations) use (&$expired) {
                foreach ($generations as $generation) {
                    $finalizer = app(GenerationFinalizer::class);

                    if ($finalizer->fail(
                        $generation,
                        'generation_timeout',
                        'The generation took too long and was stopped. Your credits were returned.',
                        [],
                        'stale_timeout',
                    )) {
                        $expired++;
                    }
                }
            });

        return $expired;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function dimensions(string $size): array
    {
        $option = collect(config('lumora.generation.size_options', []))
            ->firstWhere('value', $size);

        if (is_array($option)) {
            return [(int) $option['width'], (int) $option['height']];
        }

        [$width, $height] = array_pad(explode('x', $size, 2), 2, '1024');

        return [(int) $width ?: 1024, (int) $height ?: 1024];
    }
}
