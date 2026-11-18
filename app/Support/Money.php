<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * Integer micro-dollar money helpers.
 *
 * 1 USD = 1,000,000 micros. Floats are never used for stored or computed
 * financial values, only for display strings.
 */
final class Money
{
    public const MICROS_PER_UNIT = 1_000_000;

    public const CREDIT_MICROS = 10_000;

    public static function usdToMicros(int|string|BigDecimal $amount): int
    {
        $decimal = $amount instanceof BigDecimal
            ? $amount
            : BigDecimal::of((string) $amount);

        return $decimal
            ->multipliedBy(self::MICROS_PER_UNIT)
            ->toScale(0, RoundingMode::HalfUp)
            ->toInt();
    }

    public static function microsToUsd(int $micros, int $scale = 6): string
    {
        return BigDecimal::of($micros)
            ->dividedBy(self::MICROS_PER_UNIT, $scale, RoundingMode::HalfUp)
            ->__toString();
    }

    /**
     * Format micro-dollars for display, keeping at least 2 decimals and
     * trimming trailing zeros up to the given precision.
     */
    public static function format(int $micros, int $maximumFractionDigits = 4): string
    {
        $decimal = BigDecimal::of($micros)
            ->dividedBy(self::MICROS_PER_UNIT, $maximumFractionDigits, RoundingMode::HalfUp)
            ->__toString();

        [$whole, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');

        $fraction = rtrim($fraction, '0');
        if (strlen($fraction) < 2) {
            $fraction = str_pad($fraction, 2, '0');
        }

        return ($micros < 0 ? '-' : '').'$'.number_format(abs((int) $whole)).'.'.$fraction;
    }

    /**
     * Apply a markup expressed in basis points, rounding up so the platform
     * never under-charges for a generation.
     */
    public static function applyMarkup(int $providerMicros, int $basisPoints = 2_500): int
    {
        if ($providerMicros < 0) {
            throw new InvalidArgumentException('Provider cost cannot be negative.');
        }

        return intdiv($providerMicros * $basisPoints + 9_999, 10_000);
    }

    public static function withMarkup(int $providerMicros, int $basisPoints = 2_500): int
    {
        return $providerMicros + self::applyMarkup($providerMicros, $basisPoints);
    }

    public static function creditsToMicros(int|string $credits): int
    {
        return BigDecimal::of((string) $credits)
            ->multipliedBy(self::CREDIT_MICROS)
            ->toScale(0, RoundingMode::HalfUp)
            ->toInt();
    }

    public static function microsToCredits(int $micros, int $scale = 2): string
    {
        return BigDecimal::of($micros)
            ->dividedBy(self::CREDIT_MICROS, $scale, RoundingMode::HalfUp)
            ->__toString();
    }

    public static function clampPositive(int $value): int
    {
        return max(0, $value);
    }
}
