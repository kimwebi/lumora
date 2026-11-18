/**
 * Mirrors App\Services\Pixelforge\PricingCalculator in integer micro-dollars.
 * Used only for the live estimate; the server recalculates authoritatively.
 */
export function estimatePrice(pricing, size, quality, prompt = '', references = 0) {
    const rates = pricing?.rates ?? {};
    const charactersPerToken = Math.max(1, Number(pricing?.characters_per_token ?? 4));
    const characters = Array.from(prompt).length;
    const uncertainty = Number(pricing?.text_token_uncertainty_basis_points ?? 1500);

    const textTokens = Math.ceil(
        Math.ceil(characters / charactersPerToken) * ((10000 + uncertainty) / 10000),
    );
    const imageTokens =
        Math.max(0, references) * Number(pricing?.reference_image_tokens ?? 765);
    const outputTokens = pricing?.output_tokens?.[quality]?.[size];

    let provider;

    if (outputTokens === undefined || outputTokens === null) {
        provider = Number(pricing?.fallback_micros ?? 20000);
    } else {
        const reasoningTokens = Number(pricing?.reasoning_tokens?.[quality] ?? 0);
        const numerator =
            textTokens * Number(rates.input_text_tokens ?? 0) +
            imageTokens * Number(rates.input_image_tokens ?? 0) +
            Number(outputTokens) * Number(rates.output_tokens ?? 0) +
            reasoningTokens * Number(rates.reasoning_tokens ?? 0);

        provider = Math.ceil(numerator / 1000000);
    }

    const basisPoints = Number(pricing?.markup_basis_points ?? 2500);
    provider = Math.max(0, provider);
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
