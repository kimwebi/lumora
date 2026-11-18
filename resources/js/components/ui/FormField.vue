<script setup>
import { computed, useId } from 'vue';

const props = defineProps({
    label: { type: String, default: null },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    for: { type: String, default: null },
    required: { type: Boolean, default: false },
});

const id = useId();
const fieldId = computed(() => props.for ?? `field-${id}`);
</script>

<template>
    <div class="space-y-2">
        <div v-if="label || $slots.label" class="flex items-baseline justify-between gap-3">
            <label :for="fieldId" class="font-alt text-[11px] font-semibold uppercase tracking-[0.16em] text-subtle">
                <slot name="label">{{ label }}</slot>
                <span v-if="required" class="text-brand-500">*</span>
            </label>
            <slot name="aside" />
        </div>

        <slot :id="fieldId" :error="error" />

        <p v-if="error" class="flex items-start gap-1.5 text-[13px] font-medium text-danger">
            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path
                    fill-rule="evenodd"
                    d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0Zm-8-4a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                    clip-rule="evenodd"
                />
            </svg>
            {{ error }}
        </p>
        <p v-else-if="hint" class="text-[12.5px] leading-relaxed text-subtle">{{ hint }}</p>
    </div>
</template>
