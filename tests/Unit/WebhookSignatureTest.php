<?php

namespace Tests\Unit;

use App\Services\Pixelforge\WebhookSignature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    private const BODY = '{"id":"evt_1","type":"generation.succeeded"}';

    #[Test]
    public function it_verifies_a_timestamped_signature(): void
    {
        $timestamp = '1700000000';
        $signature = hash_hmac('sha256', $timestamp.'.'.self::BODY, self::SECRET);

        $this->assertTrue(WebhookSignature::verify(
            rawBody: self::BODY,
            signatureHeader: $signature,
            timestampHeader: $timestamp,
            secret: self::SECRET,
            scheme: 'timestamped',
            toleranceSeconds: 300,
            now: (int) $timestamp,
        ));
    }

    #[Test]
    public function it_verifies_a_stripe_style_signature_header(): void
    {
        $timestamp = '1700000000';
        $signature = hash_hmac('sha256', $timestamp.'.'.self::BODY, self::SECRET);
        $header = "t={$timestamp},v1={$signature}";

        $this->assertTrue(WebhookSignature::verify(
            rawBody: self::BODY,
            signatureHeader: $header,
            timestampHeader: null,
            secret: self::SECRET,
            scheme: 'timestamped',
            toleranceSeconds: 300,
            now: (int) $timestamp,
        ));
    }

    #[Test]
    public function it_verifies_a_raw_body_signature(): void
    {
        $signature = hash_hmac('sha256', self::BODY, self::SECRET);

        $this->assertTrue(WebhookSignature::verify(
            rawBody: self::BODY,
            signatureHeader: $signature,
            timestampHeader: null,
            secret: self::SECRET,
            scheme: 'raw',
        ));
    }

    #[Test]
    public function it_rejects_tampered_or_missing_signatures(): void
    {
        $timestamp = '1700000000';
        $signature = hash_hmac('sha256', $timestamp.'.'.self::BODY, self::SECRET);

        $this->assertFalse(WebhookSignature::verify(
            rawBody: self::BODY.' ',
            signatureHeader: $signature,
            timestampHeader: $timestamp,
            secret: self::SECRET,
            now: (int) $timestamp,
        ));

        $this->assertFalse(WebhookSignature::verify(
            rawBody: self::BODY,
            signatureHeader: null,
            timestampHeader: $timestamp,
            secret: self::SECRET,
            now: (int) $timestamp,
        ));

        $this->assertFalse(WebhookSignature::verify(
            rawBody: self::BODY,
            signatureHeader: $signature,
            timestampHeader: $timestamp,
            secret: null,
            now: (int) $timestamp,
        ));
    }

    #[Test]
    public function it_rejects_stale_timestamps(): void
    {
        $timestamp = '1700000000';
        $signature = hash_hmac('sha256', $timestamp.'.'.self::BODY, self::SECRET);

        $this->assertFalse(WebhookSignature::verify(
            rawBody: self::BODY,
            signatureHeader: $signature,
            timestampHeader: $timestamp,
            secret: self::SECRET,
            toleranceSeconds: 300,
            now: (int) $timestamp + 301,
        ));
    }
}
