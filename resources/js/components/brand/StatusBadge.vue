<script setup>
import { computed } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import { statusFor } from '@/lib/format';

const props = defineProps({
    status: { type: String, required: true },
    size: { type: String, default: 'md' },
});

const meta = computed(() => statusFor(props.status));
const inFlight = computed(() => ['pending', 'processing'].includes(props.status));
</script>

<template>
    <Badge :tone="meta.tone" dot :pulse="inFlight" :size="size">{{ meta.label }}</Badge>
</template>
