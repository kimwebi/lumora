<script setup>
import { computed } from 'vue';
import { formatUsd } from '@/lib/format';

const props = defineProps({
    estimated: { type: Object, required: true },
    actual: { type: Object, default: null },
    basisPoints: { type: Number, default: 25 },
    compact: { type: Boolean, default: false },
});

const markupPercent = computed(() => Number(props.basisPoints ?? 25));

const active = computed(() => props.actual ?? props.estimated);
</script>

<template>
    <div :class="['space-y-2.5', compact ? 'text-[12.5px]' : 'text-sm']">
        <div class="flex items-center justify-between gap-3">
            <span class="text-subtle">Provider cost</span>
            <span class="tabular text-ink">{{ formatUsd(active.provider_micros) }}</span>
        </div>

        <div class="flex items-center justify-between gap-3">
            <span class="flex items-center gap-1.5 text-subtle">
                Markup
                <span class="rounded-full bg-brand-500/10 px-1.5 py-0.5 font-alt text-[9.5px] font-bold uppercase tracking-wider text-brand-700 dark:text-brand-200">
                    {{ markupPercent }}%
                </span>
            </span>
            <span class="tabular text-ink">{{ formatUsd(active.markup_micros) }}</span>
        </div>

        <div class="hairline" />

        <div class="flex items-center justify-between gap-3">
            <span class="font-semibold text-ink">{{ actual ? 'Final charge' : 'Estimated total' }}</span>
            <span class="tabular text-[17px] font-semibold text-ink">{{ formatUsd(active.total_micros) }}</span>
        </div>

        <p v-if="!actual" class="text-[11.5px] leading-relaxed text-subtle">
            Final cost uses the provider's reported usage. Failed generations are always free.
        </p>
    </div>
</template>
