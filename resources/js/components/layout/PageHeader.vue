<script setup>
defineProps({
    eyebrow: { type: String, default: null },
    title: { type: String, required: true },
    description: { type: String, default: null },
});
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p
                v-if="eyebrow"
                class="mb-2 font-alt text-[10px] font-bold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300"
            >
                {{ eyebrow }}
            </p>

            <h1 class="text-[26px] leading-[1.15] tracking-[-0.025em] sm:text-[30px]">
                {{ title }}
            </h1>

            <p v-if="description" class="mt-2 max-w-2xl text-[13.5px] leading-relaxed text-subtle">
                {{ description }}
            </p>

            <slot name="meta" />
        </div>

        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
