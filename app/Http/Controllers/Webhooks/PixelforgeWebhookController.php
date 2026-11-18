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

        $signatureHeader = $request->header((string) config('lumora.provider.signature.header'));
        $timestampHeader = $request->header((string) config('lumora.provider.signature.timestamp_header'));

        $valid = WebhookSignature::verify(
            rawBody: $rawBody,
            signatureHeader: is_array($signatureHeader) ? ($signatureHeader[0] ?? null) : $signatureHeader,
            timestampHeader: is_array($timestampHeader) ? ($timestampHeader[0] ?? null) : $timestampHeader,
            secret: config('lumora.provider.webhook_secret'),
            scheme: (string) config('lumora.provider.signature.scheme', 'timestamped'),
            toleranceSeconds: (int) config('lumora.provider.signature.tolerance_seconds', 300),
        );

        if (! $valid) {
            Log::warning('Rejected Pixelforge webhook: invalid signature', [
                'ip' => $request->ip(),
                'has_signature' => $signatureHeader !== null,
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid JSON payload.'], 400);
        }

        $headers = array_filter([
            'event_id' => $request->header('X-Pixelforge-Event-Id'),
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
}
