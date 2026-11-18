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
            :processing="generation.status === 'pending' || generation.status === 'processing'"
            ratio="aspect-[4/3]"
            class="rounded-none"
        >
            <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-2.5">
                <StatusBadge :status="generation.status" size="sm" />
                <span class="rounded-full border border-white/10 bg-midnight/60 px-2 py-1 font-alt text-[10px] font-semibold text-white backdrop-blur-md">
                    {{ formatUsd(cost(generation), 3) }}
                </span>
            </div>

            <div
                v-if="generation.status === 'processing' || generation.status === 'pending'"
                class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-midnight/80 to-transparent p-3 pt-10"
            >
                <p class="font-alt text-[10px] font-semibold uppercase tracking-[0.18em] text-white/90">
                    {{ statusFor(generation.status).description }}
                </p>
            </div>
        </SmartImage>

        <div class="space-y-3 p-4">
            <p class="clamp-2 min-h-[2.6rem] text-[13.5px] font-medium leading-snug text-ink">
                {{ generation.prompt }}
            </p>

            <div class="flex items-center justify-between gap-2 border-t border-line/70 pt-3">
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
