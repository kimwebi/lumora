<?php

namespace App\Services\Pixelforge;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PixelforgeClient
{
    public function createGeneration(array $payload, string $idempotencyKey): array
    {
        return $this->request('post', $this->path('generations'), $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    public function getGeneration(string $providerGenerationId): array
    {
        return $this->request('get', $this->path('generations').'/'.rawurlencode($providerGenerationId));
    }

    public function usage(string $from, string $to, ?string $cursor = null): array
    {
        $query = array_filter([
            'start_date' => $from,
            'end_date' => $to,
            'cursor' => $cursor,
        ]);

        return $this->request('get', $this->path('usage'), $query);
    }

    public function isConfigured(): bool
    {
        return filled(config('lumora.provider.api_key'));
    }

    private function path(string $key): string
    {
        return (string) config("lumora.provider.paths.{$key}");
    }

    private function request(string $method, string $path, array $data = [], array $headers = []): array
    {
        $url = rtrim((string) config('lumora.provider.base_url'), '/').'/'.ltrim($path, '/');

        $request = Http::baseUrl(rtrim((string) config('lumora.provider.base_url'), '/'))
            ->withToken((string) config('lumora.provider.api_key'))
            ->acceptJson()
            ->asJson()
            ->withHeaders(array_merge([
                'X-Lumora-Client' => 'lumora/1.0',
            ], $headers))
            ->connectTimeout((int) config('lumora.provider.connect_timeout', 10))
            ->timeout((int) config('lumora.provider.timeout', 30));

        $startedAt = microtime(true);

        try {
            $response = $method === 'post'
                ? $request->post($url, $data)
                : $request->get($url, $data);
        } catch (ConnectionException $exception) {
            Log::warning('Pixelforge connection failed', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            throw new PixelforgeException(
                'Unable to reach the image provider. Please try again shortly.',
                null,
                5,
                [],
                'provider_unreachable',
            );
        } catch (\Throwable $exception) {
            Log::error('Pixelforge request crashed', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            throw new PixelforgeException(
                'The image provider returned an unexpected error.',
                null,
                10,
                [],
                'provider_error',
            );
        }

        $duration = (int) round((microtime(true) - $startedAt) * 1000);

        Log::info('Pixelforge request', [
            'method' => strtoupper($method),
            'path' => $path,
            'url' => $url,
            'status' => $response->status(),
            'duration_ms' => $duration,
            'request_id' => $response->header('X-Request-Id'),
        ]);

        if ($response->failed()) {
            Log::warning('Pixelforge request rejected', [
                'method' => strtoupper($method),
                'url' => $url,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            throw $this->exceptionFrom($response);
        }

        $json = $response->json();

        return is_array($json) ? $json : ['data' => $json];
    }

    private function exceptionFrom(Response $response): PixelforgeException
    {
        $body = $response->json();
        $message = 'The image provider rejected the request.';

        if (is_array($body)) {
            $message = (string) (
                data_get($body, 'error.message')
                ?? data_get($body, 'message')
                ?? data_get($body, 'error')
                ?? $message
            );

            if (! is_string($message)) {
                $message = 'The image provider rejected the request.';
            }
        }

        $retryAfter = null;
        $header = $response->header('Retry-After');
        if (is_numeric($header)) {
            $retryAfter = (int) $header;
        } elseif (is_string($header) && $header !== '') {
            $timestamp = strtotime($header);
            if ($timestamp !== false) {
                $retryAfter = max(1, $timestamp - time());
            }
        }

        $code = match (true) {
            $response->status() === 429 => 'provider_rate_limited',
            $response->status() >= 500 => 'provider_unavailable',
            $response->status() === 422 => 'provider_validation_failed',
            default => 'provider_rejected',
        };

        return new PixelforgeException(
            $message,
            $response->status(),
            $retryAfter,
            is_array($body) ? $body : [],
            $code,
        );
    }
}
