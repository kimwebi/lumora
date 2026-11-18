<script setup>
defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number], default: '' });

defineProps({
    type: { type: String, default: 'text' },
    error: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
});
</script>

<template>
    <div class="relative">
        <span v-if="$slots.leading" class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-subtle">
            <slot name="leading" />
        </span>

        <input
            v-model="model"
            :type="type"
            :class="[
                'w-full rounded-xl border bg-input text-ink shadow-[inset_0_1px_0_rgba(255,255,255,0.02)] transition-all duration-200 placeholder:text-subtle/60 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12',
                size === 'sm' ? 'h-9 px-3 text-[13px]' : 'h-11 px-3.5 text-sm',
                $slots.leading ? 'pl-10' : '',
                error ? 'border-danger/70 focus:border-danger focus:ring-danger/15' : 'border-line',
            ]"
            v-bind="$attrs"
        />

        <span v-if="$slots.trailing" class="absolute inset-y-0 right-3 flex items-center text-subtle">
            <slot name="trailing" />
        </span>
    </div>
</template>
