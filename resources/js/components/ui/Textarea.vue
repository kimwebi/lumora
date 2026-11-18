<script setup>
defineOptions({ inheritAttrs: false });

const model = defineModel({ type: String, default: '' });

defineProps({
    rows: { type: [Number, String], default: 5 },
    error: { type: Boolean, default: false },
    resize: { type: Boolean, default: false },
});
</script>

<template>
    <textarea
        v-model="model"
        :rows="rows"
        :class="[
            'w-full rounded-xl border bg-input px-4 py-3 text-sm leading-relaxed text-ink shadow-[inset_0_1px_0_rgba(255,255,255,0.02)] transition-all duration-200 placeholder:text-subtle/60 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12',
            error ? 'border-danger/70 focus:border-danger focus:ring-danger/15' : 'border-line',
            resize ? 'resize-y' : 'resize-none',
        ]"
        v-bind="$attrs"
    />
</template>
