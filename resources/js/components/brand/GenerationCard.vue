<script setup>
import { ArrowUpRight, Clock3 } from 'lucide-vue-next';
import SmartImage from '@/components/brand/SmartImage.vue';
import StatusBadge from '@/components/brand/StatusBadge.vue';
import { formatCredits, formatRelative, formatUsd, statusFor } from '@/lib/format';

const props = defineProps({
    generation: { type: Object, required: true },
});

defineEmits(['open']);

const cost = (generation) =>
    generation.actual?.total_micros ?? generation.estimated?.total_micros ?? 0;

const inFlight = (generation) =>
    generation.status === 'pending' || generation.status === 'processing';
</script>

<template>
    <button
        type="button"
        class="group relative w-full overflow-hidden rounded-2xl border border-line bg-surface text-left shadow-card transition-all duration-300 hover:-translate-y-1 hover:border-brand-400/45 hover:shadow-lift focus-ring"
        :aria-label="`Open generation details: ${generation.prompt}`"
        @click="$emit('open', generation)"
    >
        <SmartImage
            :src="generation.image_url"
            :alt="generation.prompt"
            :processing="inFlight(generation)"
            ratio="aspect-[4/3]"
            fit="contain"
            class="rounded-none"
        />

        <div class="space-y-2.5 p-4">
            <div class="flex items-center justify-between gap-2">
                <StatusBadge :status="generation.status" size="sm" />
                <span class="rounded-full border border-line bg-elevated px-2 py-1 font-alt text-[10px] font-semibold text-subtle">
                    {{ formatUsd(cost(generation), 3) }}
                </span>
            </div>

            <p class="whitespace-pre-line text-[13.5px] font-medium leading-snug text-ink [overflow-wrap:anywhere]">
                {{ generation.prompt }}
            </p>

            <p
                v-if="inFlight(generation)"
                class="font-alt text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300"
            >
                {{ statusFor(generation.status).description }}
            </p>

            <div class="flex items-center justify-between gap-2 border-t border-line/70 pt-2.5">
                <div class="flex min-w-0 items-center gap-2 text-[11.5px] text-subtle">
                    <Clock3 class="h-3.5 w-3.5 shrink-0" />
                    <span class="truncate">{{ formatRelative(generation.created_at) }}</span>
                    <span class="text-line">·</span>
                    <span class="truncate">{{ generation.size }}</span>
                </div>

                <span class="flex shrink-0 items-center gap-1 font-alt text-[11px] font-semibold uppercase tracking-wide text-brand-700 dark:text-brand-300">
                    {{ formatCredits(cost(generation)) }} cr
                    <ArrowUpRight class="h-3.5 w-3.5 transition-transform duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                </span>
            </div>
        </div>
    </button>
</template>
