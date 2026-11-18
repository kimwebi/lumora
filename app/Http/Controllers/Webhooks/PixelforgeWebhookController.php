<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Generations\WebhookProcessor;
use App\Services\Pixelforge\WebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PixelforgeWebhookController extends Controller
{
    public function __invoke(Request $request, WebhookProcessor $processor): JsonResponse
    {
        $rawBody = $request->getContent();

        $configuredHeader = (string) config('lumora.provider.signature.header');
        $configuredTimestampHeader = (string) config('lumora.provider.signature.timestamp_header');

        $signatureHeaders = WebhookSignature::candidates($request->headers->all(), $configuredHeader);
        $timestampHeader = $request->header($configuredTimestampHeader) ?? $this->findTimestampHeader($request);

        $matchedHeader = null;

        foreach ($signatureHeaders as $name => $signatureHeader) {
            $valid = WebhookSignature::verify(
                rawBody: $rawBody,
                signatureHeader: $signatureHeader,
                timestampHeader: is_array($timestampHeader) ? ($timestampHeader[0] ?? null) : $timestampHeader,
                secret: config('lumora.provider.webhook_secret'),
                scheme: (string) config('lumora.provider.signature.scheme', 'timestamped'),
                toleranceSeconds: (int) config('lumora.provider.signature.tolerance_seconds', 300),
            );

            if ($valid) {
                $matchedHeader = $name;

                break;
            }
        }

        if ($matchedHeader === null) {
            Log::warning('Rejected Pixelforge webhook: invalid signature', [
                'ip' => $request->ip(),
                'configured_header' => $configuredHeader,
                'has_signature' => $signatureHeaders !== [],
                'signature_headers' => array_keys($signatureHeaders),
                'header_names' => array_keys($request->headers->all()),
                'secret_configured' => filled(config('lumora.provider.webhook_secret')),
                'scheme' => config('lumora.provider.signature.scheme'),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        if (strcasecmp($matchedHeader, $configuredHeader) !== 0) {
            Log::info('Pixelforge webhook verified via an alternate signature header', [
                'header' => $matchedHeader,
            ]);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid JSON payload.'], 400);
        }

        $headers = array_filter([
            'event_id' => $request->header('Pixelforge-Event-Id', $request->header('X-Pixelforge-Event-Id')),
            'event_type' => $request->header('Pixelforge-Event-Type'),
            'delivery_attempt' => $request->header('Pixelforge-Delivery-Attempt'),
            'request_id' => $request->header('X-Request-Id'),
            'user_agent' => $request->userAgent(),
            'content_type' => $request->header('Content-Type'),
        ]);

        try {
            $result = $processor->process($payload, $rawBody, $headers);
        } catch (Throwable $exception) {
            Log::error('Pixelforge webhook could not be processed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }

        return response()->json(['received' => true] + $result);
    }

    /**
     * Use the configured timestamp header, or the first header that looks like
     * a delivery timestamp, so provider header renames do not break replay checks.
     */
    private function findTimestampHeader(Request $request): ?string
    {
        foreach ($request->headers->all() as $name => $values) {
            if (! str_contains($name, 'timestamp')) {
                continue;
            }

            $value = $values[0] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
