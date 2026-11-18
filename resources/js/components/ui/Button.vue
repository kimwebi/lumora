<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Spinner from './Spinner.vue';

const props = defineProps({
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});

const variants = {
    primary:
        'bg-brand-600 text-white shadow-[0_12px_30px_-14px_rgba(124,58,237,0.9)] hover:bg-brand-500 hover:shadow-[0_16px_40px_-16px_rgba(124,58,237,0.95)]',
    secondary:
        'border border-line bg-surface text-ink hover:border-brand-400/70 hover:text-brand-700 dark:hover:text-brand-200',
    ghost: 'text-subtle hover:bg-elevated hover:text-ink',
    subtle: 'bg-brand-500/10 text-brand-700 hover:bg-brand-500/15 dark:text-brand-200',
    danger: 'bg-danger text-white hover:bg-red-600',
    'danger-ghost': 'text-danger hover:bg-danger/10',
    outline:
        'border border-brand-500/40 text-brand-700 hover:border-brand-500 hover:bg-brand-500/8 dark:text-brand-200',
};

const sizes = {
    sm: 'h-9 px-3.5 text-[13px]',
    md: 'h-11 px-4 text-sm',
    lg: 'h-12 px-6 text-[15px]',
    icon: 'h-10 w-10',
    'icon-sm': 'h-8 w-8',
};

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-xl font-medium tracking-[-0.01em] transition-all duration-200 press focus-ring disabled:pointer-events-none disabled:opacity-50',
    variants[props.variant] ?? variants.primary,
    sizes[props.size] ?? sizes.md,
    props.block ? 'w-full' : '',
]);

const isDisabled = computed(() => props.disabled || props.loading);
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href || undefined"
        :type="href ? undefined : type"
        :disabled="href ? undefined : isDisabled"
        :aria-disabled="isDisabled || undefined"
        :aria-busy="loading || undefined"
        :class="classes"
    >
        <Spinner v-if="loading" />
        <slot v-else name="icon" />
        <slot />
    </component>
</template>
