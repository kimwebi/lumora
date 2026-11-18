<script setup>
import { Link } from '@inertiajs/vue3';
import { routes } from '@/lib/routes';

defineProps({
    iconOnly: { type: Boolean, default: false },
    showTagline: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
    href: { type: String, default: routes.home },
});

const iconSizes = {
    sm: 'h-8 w-8 rounded-[10px]',
    md: 'h-9 w-9 rounded-[11px]',
    lg: 'h-11 w-11 rounded-[13px]',
};

const textSizes = {
    sm: 'text-[16px]',
    md: 'text-[19px]',
    lg: 'text-[22px]',
};
</script>

<template>
    <Link :href="href" class="group inline-flex items-center gap-2.5" aria-label="Lumora home">
        <span
            :class="[
                'relative grid shrink-0 place-items-center overflow-hidden transition-transform duration-300 group-hover:scale-[1.04]',
                iconSizes[size] ?? iconSizes.md,
            ]"
        >
            <span class="orb absolute inset-0" aria-hidden="true" />
            <span class="absolute inset-0 rounded-[inherit] ring-1 ring-inset ring-white/20" aria-hidden="true" />

            <svg viewBox="0 0 32 32" fill="none" class="relative h-[62%] w-[62%] text-white" aria-hidden="true">
                <ellipse cx="16" cy="16" rx="13" ry="5.4" stroke="white" stroke-opacity="0.65" stroke-width="1.4" transform="rotate(-28 16 16)" />
                <circle cx="16" cy="16" r="4.1" fill="white" fill-opacity="0.92" />
                <circle cx="26.5" cy="9.5" r="1.5" fill="#22D3EE" />
            </svg>
        </span>

        <span v-if="!iconOnly" class="flex min-w-0 flex-col leading-none">
            <span :class="['font-display font-semibold tracking-[-0.02em] text-ink', textSizes[size] ?? textSizes.md]">
                Lumora
            </span>
            <span v-if="showTagline" class="mt-1 font-alt text-[8.5px] font-semibold uppercase tracking-[0.3em] text-subtle">
                Image Studio
            </span>
        </span>
    </Link>
</template>
