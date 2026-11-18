<script setup>
defineProps({
    title: { type: String, default: 'Nothing here yet' },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-line bg-surface/60 px-6 py-14 text-center sm:py-20">
        <div class="relative mb-5 grid h-16 w-16 place-items-center rounded-2xl border border-brand-500/20 bg-brand-500/8 text-brand-600 dark:text-brand-300">
            <div class="absolute inset-0 rounded-2xl bg-brand-500/10 blur-xl" aria-hidden="true" />
            <div class="relative">
                <slot name="icon" />
            </div>
        </div>

        <h3 class="text-lg font-semibold">{{ title }}</h3>
        <p v-if="description" class="mt-2 max-w-md text-sm leading-relaxed text-subtle">{{ description }}</p>

        <div v-if="$slots.action" class="mt-6">
            <slot name="action" />
        </div>
    </div>
</template>
