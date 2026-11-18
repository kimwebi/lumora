<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: { type: String, default: 'neutral' },
    dot: { type: Boolean, default: false },
    pulse: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
});

const tones = {
    neutral: 'bg-elevated text-subtle ring-line',
    brand: 'bg-brand-500/10 text-brand-700 ring-brand-500/20 dark:text-brand-200',
    success: 'bg-success/10 text-emerald-700 ring-success/25 dark:text-emerald-300',
    danger: 'bg-danger/10 text-red-700 ring-danger/25 dark:text-red-300',
    pending: 'bg-pending/12 text-amber-700 ring-pending/30 dark:text-amber-300',
    processing: 'bg-processing/12 text-brand-700 ring-processing/25 dark:text-brand-200',
    info: 'bg-info/12 text-sky-700 ring-info/25 dark:text-sky-300',
    dark: 'bg-white/10 text-white ring-white/20',
};

const dotTones = {
    neutral: 'bg-subtle',
    brand: 'bg-brand-500',
    success: 'bg-success',
    danger: 'bg-danger',
    pending: 'bg-pending',
    processing: 'bg-processing',
    info: 'bg-info',
    dark: 'bg-white',
};

const classes = computed(() => [
    'inline-flex items-center gap-1.5 rounded-full font-alt font-semibold uppercase tracking-[0.08em] ring-1 ring-inset',
    props.size === 'sm' ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-1 text-[11px]',
    tones[props.tone] ?? tones.neutral,
]);
</script>

<template>
    <span :class="classes">
        <span v-if="dot" class="relative flex h-1.5 w-1.5">
            <span
                v-if="pulse"
                class="absolute inline-flex h-full w-full rounded-full opacity-60 animate-ping"
                :class="dotTones[tone] ?? dotTones.neutral"
            />
            <span class="relative inline-flex h-1.5 w-1.5 rounded-full" :class="dotTones[tone] ?? dotTones.neutral" />
        </span>
        <slot />
    </span>
</template>
