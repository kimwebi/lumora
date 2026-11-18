<?php

namespace App\Services\Generations;

use App\Models\Generation;
use App\Models\WebhookEvent;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class WebhookProcessor
{
    public function __construct(private readonly GenerationFinalizer $finalizer) {}

    public function process(array $payload, string $rawBody, array $headers = []): array
    {
        $eventId = $this->eventId($payload, $rawBody);
        $occurredAt = $this->occurredAt($payload);

        $event = WebhookEvent::query()->where('provider_event_id', $eventId)->first();

        if ($event?->processed) {
            return ['outcome' => 'duplicate', 'event_id' => $eventId];
        }

        $event ??= new WebhookEvent(['provider_event_id' => $eventId]);

        $event->fill([
            'type' => $this->eventType($payload),
            'status' => $this->finalizer->providerStatus($payload),
            'provider_generation_id' => $this->finalizer->providerGenerationId($payload),
            'occurred_at' => $occurredAt,
            'payload' => $rawBody,
            'headers' => $headers,
            'signature_valid' => true,
        ]);

        if (! $event->exists) {
            $event->save();
        }

        try {
            $generation = $this->findGeneration($payload);

            if (! $generation && $this->isPing($payload)) {
                $event->forceFill([
                    'processed' => true,
                    'ignored' => true,
                    'error' => null,
                ])->save();

                return ['outcome' => 'ignored', 'event_id' => $eventId];
            }

            if (! $generation) {
                $event->forceFill(['error' => 'generation_not_found'])->save();

                return ['outcome' => 'unmatched', 'event_id' => $eventId];
            }

            $event->generation_id = $generation->id;
            $event->provider_generation_id ??= $generation->provider_generation_id;

            if ($generation->isSettled()) {
                $event->forceFill([
                    'processed' => true,
                    'ignored' => true,
                    'error' => 'already_settled',
                ])->save();

                return ['outcome' => 'ignored', 'event_id' => $eventId];
            }

            if (! $this->finalizer->acceptEvent($generation, $occurredAt)) {
                $event->forceFill([
                    'processed' => true,
                    'ignored' => true,
                    'error' => 'out_of_order',
                ])->save();

                return ['outcome' => 'ignored', 'event_id' => $eventId];
            }

            $status = $this->normalizedStatus($payload);

            $applied = match ($status) {
                'succeeded' => $this->finalizer->succeed($generation, $payload, 'webhook', $occurredAt),
                'failed' => $this->finalizer->fail(
                    $generation,
                    $this->errorCode($payload),
                    $this->errorMessage($payload),
                    $payload,
                    'webhook',
                    $occurredAt,
                ),
                default => tap(false, function () use ($generation, $payload) {
                    $this->finalizer->markProcessing($generation, $payload, 'webhook');
                }),
            };

            $event->forceFill([
                'processed' => true,
                'status' => $status,
                'error' => $status === 'processing' ? null : ($applied ? null : 'not_applied'),
            ])->save();

            return [
                'outcome' => $applied ? 'processed' : 'ignored',
                'status' => $status,
                'event_id' => $eventId,
                'generation' => $generation->uuid,
            ];
        } catch (Throwable $exception) {
            $event->forceFill(['error' => Str::limit($exception->getMessage(), 500)])->save();

            Log::error('Webhook processing failed', [
                'event_id' => $eventId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function replayPending(): int
    {
        $replayed = 0;

        WebhookEvent::query()
            ->where('processed', false)
            ->whereNotNull('error')
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->each(function (WebhookEvent $event) use (&$replayed) {
                $generation = $this->findGeneration($event->payloadArray());

                if (! $generation) {
                    if ($this->isPing($event->payloadArray())) {
                        $event->forceFill([
                            'processed' => true,
                            'ignored' => true,
                            'error' => null,
                        ])->save();
                    }

                    return;
                }

                $event->forceFill(['error' => null])->save();

                try {
                    $this->process($event->payloadArray(), (string) $event->payload, $event->headers ?? []);
                    $replayed++;
                } catch (Throwable $exception) {
                    Log::warning('Webhook replay failed', [
                        'event_id' => $event->provider_event_id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });

        return $replayed;
    }

    private function findGeneration(array $payload): ?Generation
    {
        $providerId = $this->finalizer->providerGenerationId($payload);
        $uuid = Arr::get($payload, 'data.object.metadata.generation_uuid')
            ?? Arr::get($payload, 'data.metadata.generation_uuid')
            ?? Arr::get($payload, 'metadata.generation_uuid')
            ?? Arr::get($payload, 'client_reference_id');

        if ($providerId) {
            $generation = Generation::query()->where('provider_generation_id', $providerId)->first();

            if ($generation) {
                return $generation;
            }
        }

        if (is_string($uuid) && Str::isUuid($uuid)) {
            return Generation::query()->where('uuid', $uuid)->first();
        }

        return null;
    }

    private function normalizedStatus(array $payload): string
    {
        $status = Str::lower((string) (
            $this->finalizer->providerStatus($payload)
            ?? Arr::get($payload, 'type')
            ?? Arr::get($payload, 'event')
            ?? ''
        ));

        return match (true) {
            str_contains($status, 'succeed'),
            str_contains($status, 'complete'),
            str_contains($status, 'success') => 'succeeded',
            str_contains($status, 'fail'),
            str_contains($status, 'error'),
            str_contains($status, 'cancel') => 'failed',
            default => 'processing',
        };
    }

    private function errorCode(array $payload): string
    {
        foreach (['data.object.error.code', 'error.code', 'data.error.code', 'failure_code', 'code'] as $path) {
            $value = Arr::get($payload, $path);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return 'generation_failed';
    }

    private function errorMessage(array $payload): ?string
    {
        foreach (['data.object.error.message', 'error.message', 'data.error.message', 'failure_reason', 'message'] as $path) {
            $value = Arr::get($payload, $path);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function eventId(array $payload, string $rawBody): string
    {
        foreach (['id', 'event_id', 'data.event_id', 'event.id'] as $path) {
            $value = Arr::get($payload, $path);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return 'evt_'.hash('sha256', $rawBody);
    }

    private function eventType(array $payload): ?string
    {
        $value = Arr::get($payload, 'type') ?? Arr::get($payload, 'event');

        return is_string($value) ? $value : null;
    }

    private function isPing(array $payload): bool
    {
        $type = Str::lower((string) $this->eventType($payload));

        return $type === 'ping' || $type === 'webhook.ping';
    }

    private function occurredAt(array $payload): ?Carbon
    {
        foreach (['created_at', 'occurred_at', 'timestamp', 'data.completed_at', 'data.created_at'] as $path) {
            $value = Arr::get($payload, $path);

            if (is_numeric($value)) {
                return Carbon::createFromTimestamp((int) $value);
            }

            if (is_string($value) && $value !== '') {
                try {
                    return Carbon::parse($value);
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return null;
    }
}
