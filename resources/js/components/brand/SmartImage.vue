<script setup>
import { computed, ref, watch } from 'vue';
import { ImageOff, Sparkles } from 'lucide-vue-next';

const props = defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: '' },
    processing: { type: Boolean, default: false },
    ratio: { type: String, default: 'aspect-[4/3]' },
    rounded: { type: String, default: '' },
    fit: { type: String, default: 'cover' },
});

const failed = ref(false);

watch(() => props.src, () => {
    failed.value = false;
});

const showImage = computed(() => Boolean(props.src) && !failed.value);

const objectFit = computed(() => (props.fit === 'contain' ? 'object-contain' : 'object-cover'));
</script>

<template>
    <div :class="['relative overflow-hidden bg-elevated', ratio, rounded]">
        <img
            v-if="showImage"
            :src="src"
            :alt="alt"
            loading="lazy"
            decoding="async"
            :class="['h-full w-full transition-transform duration-700 ease-out', objectFit]"
            @error="failed = true"
        />

        <template v-else-if="processing">
            <div class="absolute inset-0 animate-shimmer" aria-hidden="true" />
            <div class="absolute inset-0 dot-grid opacity-40" aria-hidden="true" />
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3">
                <span class="relative grid h-11 w-11 place-items-center rounded-full bg-brand-500/15 ring-1 ring-brand-400/30">
                    <span class="absolute inset-0 rounded-full animate-pulse-ring" aria-hidden="true" />
                    <Sparkles class="h-5 w-5 animate-pulse text-brand-500 dark:text-brand-300" />
                </span>
                <span class="font-alt text-[10px] font-semibold uppercase tracking-[0.22em] text-subtle">Rendering</span>
            </div>
        </template>

        <div v-else class="absolute inset-0 grid place-items-center bg-[radial-gradient(circle_at_30%_25%,rgba(139,92,246,0.22),transparent_60%)]">
            <div class="flex flex-col items-center gap-2.5 text-subtle/70">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-surface/70 ring-1 ring-line">
                    <ImageOff class="h-5 w-5" />
                </span>
                <span class="font-alt text-[10px] uppercase tracking-[0.2em]">No preview</span>
            </div>
        </div>

        <slot />
    </div>
</template>
