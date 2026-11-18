<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, default: '' },
    initials: { type: String, default: null },
    size: { type: String, default: 'md' },
});

const sizes = {
    xs: 'h-7 w-7 text-[10px]',
    sm: 'h-8 w-8 text-[11px]',
    md: 'h-10 w-10 text-xs',
    lg: 'h-14 w-14 text-base',
};

const computedInitials = computed(() => {
    if (props.initials) return props.initials;

    return (props.name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
});
</script>

<template>
    <span
        :class="[
            'grid shrink-0 place-items-center rounded-full bg-[linear-gradient(135deg,#8b5cf6,#6d28d9_55%,#22d3ee)] font-alt font-bold uppercase text-white ring-1 ring-white/15',
            sizes[size] ?? sizes.md,
        ]"
        :title="name"
    >
        {{ computedInitials }}
    </span>
</template>
