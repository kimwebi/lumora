<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: String, required: true },
    hint: { type: String, default: null },
    icon: { type: [Object, Function], default: null },
    tone: { type: String, default: 'brand' },
    loading: { type: Boolean, default: false },
});

const tones = {
    brand: 'bg-brand-500/10 text-brand-600 ring-brand-500/15 dark:text-brand-300',
    cyan: 'bg-accent/10 text-cyan-600 ring-accent/20 dark:text-cyan-300',
    success: 'bg-success/10 text-emerald-600 ring-success/20 dark:text-emerald-300',
    pending: 'bg-pending/10 text-amber-600 ring-pending/20 dark:text-amber-300',
    info: 'bg-info/10 text-sky-600 ring-info/20 dark:text-sky-300',
    neutral: 'bg-elevated text-subtle ring-line',
};

const toneClass = computed(() => tones[props.tone] ?? tones.brand);
</script>

<template>
    <div class="group relative overflow-hidden rounded-2xl border border-line bg-surface p-4 shadow-card transition-all duration-300 hover:border-brand-400/40 hover:shadow-lift sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <p class="font-alt text-[10.5px] font-semibold uppercase leading-tight tracking-[0.14em] text-subtle">{{ label }}</p>
            <span v-if="icon" :class="['grid h-8 w-8 shrink-0 place-items-center rounded-lg ring-1 ring-inset', toneClass]">
                <component :is="icon" class="h-4 w-4" />
            </span>
        </div>

        <div class="mt-3">
            <div v-if="loading" class="h-8 w-24 animate-shimmer rounded-lg" />
            <p v-else class="tabular text-[26px] font-semibold leading-none tracking-tight text-ink sm:text-[28px]">{{ value }}</p>
            <p v-if="hint" class="mt-1.5 text-[12px] leading-snug text-subtle">{{ hint }}</p>
        </div>

        <div class="pointer-events-none absolute -bottom-8 -right-8 h-20 w-20 rounded-full bg-brand-500/6 blur-2xl transition-opacity duration-500 group-hover:bg-brand-500/10" aria-hidden="true" />
    </div>
</template>
