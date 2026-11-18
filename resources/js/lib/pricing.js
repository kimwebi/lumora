/**
 * Mirrors App\Services\Pixelforge\PricingCalculator in integer micro-dollars.
 * Used only for the live estimate; the server recalculates authoritatively.
 */
export function estimatePrice(pricing, size, quality) {
    const unit = Number(pricing?.rates?.[size]?.[quality] ?? pricing?.fallback_micros ?? 20000);
    const basisPoints = Number(pricing?.markup_basis_points ?? 2500);
    const provider = Math.max(0, Math.round(unit));
    const markup = Math.ceil((provider * basisPoints) / 10000);

    return {
        provider,
        markup,
        total: provider + markup,
        markupBasisPoints: basisPoints,
        size,
        quality,
    };
}
