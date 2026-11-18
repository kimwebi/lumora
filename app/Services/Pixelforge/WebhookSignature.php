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

        if ($scheme !== 'raw' && $timestamp !== null) {
            $skew = abs(($now ?? time()) - (int) $timestamp);

            if ($toleranceSeconds > 0 && $skew > $toleranceSeconds) {
                return false;
            }
        }

        /*
         * Try every signing input the documented styles allow:
         *  - "{timestamp}.{rawBody}" for timestamped deliveries
         *  - "{rawBody}" for raw deliveries, and for providers that send a
         *    plain signature without a timestamp header.
         */
        $signingInputs = [$rawBody];

        if ($scheme !== 'raw' && $timestamp !== null) {
            $signingInputs[] = $timestamp.'.'.$rawBody;
        }

        foreach (array_unique($signingInputs) as $signingInput) {
            $expected = hash_hmac('sha256', $signingInput, $secret);

            foreach ($signatures as $signature) {
                if (hash_equals($expected, $signature)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Resolve the signature header, falling back to any other signature-like
     * header so deployments survive provider header naming changes.
     *
     * @param  array<string, array<int, string|null>>  $headers  Lowercase header bag.
     * @return array<string, string> Header name to raw value, configured header first.
     */
    public static function candidates(array $headers, string $configuredHeader): array
    {
        $configured = strtolower($configuredHeader);
        $candidates = [];

        foreach ($headers as $name => $values) {
            $value = is_array($values) ? ($values[0] ?? null) : $values;

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            if ($name !== $configured && ! str_contains($name, 'sig')) {
                continue;
            }

            $candidates[$name] = $value;
        }

        if (isset($candidates[$configured])) {
            $candidates = [$configured => $candidates[$configured]] + $candidates;
        }

        return $candidates;
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
