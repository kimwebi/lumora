<?php

namespace App\Services\Pixelforge;

use RuntimeException;

class PixelforgeException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?int $retryAfter = null,
        public readonly array $response = [],
        public readonly string $errorCode = 'provider_error',
    ) {
        parent::__construct($message);
    }

    public function isRetryable(): bool
    {
        return $this->status === null
            || $this->status === 429
            || $this->status >= 500;
    }

    public function isClientError(): bool
    {
        return $this->status !== null && $this->status >= 400 && $this->status < 500 && $this->status !== 429;
    }

    /**
     * The provider also answers 404 while a resource is still propagating, so a
     * not-found on a create request is treated as transient rather than final.
     */
    public function isNotFound(): bool
    {
        return $this->status === 404;
    }

    public function retryDelay(int $default = 10): int
    {
        return $this->retryAfter !== null ? max(1, $this->retryAfter) : $default;
    }
}
