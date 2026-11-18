<script setup>
import { computed } from 'vue';

const props = defineProps({
    as: { type: String, default: 'div' },
    padding: { type: String, default: 'md' },
    hoverable: { type: Boolean, default: false },
    tone: { type: String, default: 'surface' },
});

const paddings = {
    none: '',
    sm: 'p-4',
    md: 'p-5 sm:p-6',
    lg: 'p-6 sm:p-8',
};

const tones = {
    surface: 'bg-surface border-line',
    elevated: 'bg-elevated border-line',
    glass: 'glass border-line',
    quiet: 'bg-muted border-line',
};

const classes = computed(() => [
    'rounded-2xl border shadow-card transition-all duration-300',
    paddings[props.padding] ?? paddings.md,
    tones[props.tone] ?? tones.surface,
    props.hoverable ? 'hover:-translate-y-0.5 hover:shadow-lift hover:border-brand-400/40' : '',
]);
</script>

<template>
    <component :is="as" :class="classes">
        <slot />
    </component>
</template>
