<?php

namespace App\Services\Pixelforge;

/**
 * Stateless HMAC webhook signature verification.
 *
 * Supports two documented styles:
 *  - "timestamped": signature = HMAC-SHA256("{timestamp}.{rawBody}") and the
 *    header may be a Stripe-style list such as "t=1700000000,v1=abcdef".
 *  - "raw": signature = HMAC-SHA256(rawBody).
 */
class WebhookSignature
{
    public static function verify(
        string $rawBody,
        ?string $signatureHeader,
        ?string $timestampHeader,
        ?string $secret,
        string $scheme = 'timestamped',
        int $toleranceSeconds = 300,
        ?int $now = null,
    ): bool {
        if (blank($secret) || blank($signatureHeader)) {
            return false;
        }

        [$timestamp, $signatures] = self::parseHeader($signatureHeader, $timestampHeader);

        if ($signatures === []) {
            return false;
        }

        $signingInput = $scheme === 'raw'
            ? $rawBody
            : ($timestamp !== null ? $timestamp.'.'.$rawBody : $rawBody);

        if ($scheme !== 'raw') {
            if ($timestamp === null) {
                return false;
            }

            $skew = abs(($now ?? time()) - (int) $timestamp);
            if ($toleranceSeconds > 0 && $skew > $toleranceSeconds) {
                return false;
            }
        }

        $expected = hash_hmac('sha256', $signingInput, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: ?string, 1: list<string>}
     */
    private static function parseHeader(string $header, ?string $timestampHeader): array
    {
        $timestamp = $timestampHeader !== null && $timestampHeader !== '' ? $timestampHeader : null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (str_contains($part, '=')) {
                [$key, $value] = array_map('trim', explode('=', $part, 2));

                if (in_array($key, ['t', 'timestamp', 'ts'], true)) {
                    $timestamp = $value;
                } elseif (in_array($key, ['v1', 'v0', 'signature', 'sig'], true)) {
                    $signatures[] = $value;
                }
            } else {
                $signatures[] = $part;
            }
        }

        $signatures = array_values(array_filter(
            array_map(fn (string $value) => preg_replace('/^sha256=/i', '', trim($value)), $signatures),
            fn (string $value) => $value !== '',
        ));

        return [$timestamp, $signatures];
    }
}
