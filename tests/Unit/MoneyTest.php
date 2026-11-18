<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    #[Test]
    public function it_rounds_markup_up_so_the_platform_never_undercharges(): void
    {
        $this->assertSame(5_000, Money::applyMarkup(20_000));
        $this->assertSame(25_000, Money::withMarkup(20_000));

        // 1 micro at 25% is 0.25 micros, which must round up to a full micro.
        $this->assertSame(1, Money::applyMarkup(1));
        $this->assertSame(2, Money::withMarkup(1));

        // 3 micros at 25% is 0.75 micros, again rounded up.
        $this->assertSame(1, Money::applyMarkup(3));
    }

    #[Test]
    public function it_respects_a_custom_markup(): void
    {
        $this->assertSame(2_000, Money::applyMarkup(10_000, 2_000));
        $this->assertSame(12_000, Money::withMarkup(10_000, 2_000));
        $this->assertSame(0, Money::applyMarkup(0));
    }

    #[Test]
    public function it_converts_between_usd_credits_and_micros(): void
    {
        $this->assertSame(10_000, Money::usdToMicros('0.01'));
        $this->assertSame(5_000_000, Money::usdToMicros('5'));
        $this->assertSame(10_000, Money::creditsToMicros(1));
        $this->assertSame(1_000_000, Money::creditsToMicros('100'));
        $this->assertSame('0.010000', Money::microsToUsd(10_000));
        $this->assertSame('1.00', Money::microsToCredits(10_000));
    }

    #[Test]
    public function it_formats_micros_for_display(): void
    {
        $this->assertSame('$0.025', Money::format(25_000));
        $this->assertSame('$5.00', Money::format(5_000_000, 2));
        $this->assertSame('$0.01', Money::format(10_000, 2));
        $this->assertSame('-$0.01', Money::format(-10_000, 2));
    }

    #[Test]
    public function it_rejects_negative_provider_costs(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::applyMarkup(-1);
    }
}
