<script setup>
import { computed } from 'vue';

const props = defineProps({
    text: { type: String, required: true },
    tag: { type: String, default: 'p' },
});

/**
 * Lightweight, XSS-safe inline formatting: `code` and **bold**.
 * Everything is rendered as text nodes, never as raw HTML.
 */
const parts = computed(() =>
    String(props.text ?? '')
        .split(/(`[^`]+`|\*\*[^*]+\*\*)/g)
        .filter((part) => part !== '')
        .map((part) => {
            if (part.startsWith('`') && part.endsWith('`')) {
                return { type: 'code', value: part.slice(1, -1) };
            }

            if (part.startsWith('**') && part.endsWith('**')) {
                return { type: 'strong', value: part.slice(2, -2) };
            }

            return { type: 'text', value: part };
        }),
);
</script>

<template>
    <component :is="tag">
        <template v-for="(part, index) in parts" :key="index">
            <code
                v-if="part.type === 'code'"
                class="rounded-md border border-line/70 bg-elevated/80 px-1.5 py-0.5 font-mono text-[0.82em] font-medium text-ink"
            >
                {{ part.value }}
            </code>
            <strong v-else-if="part.type === 'strong'" class="font-semibold text-ink">{{ part.value }}</strong>
            <template v-else>{{ part.value }}</template>
        </template>
    </component>
</template>
