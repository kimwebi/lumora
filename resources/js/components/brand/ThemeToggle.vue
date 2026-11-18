<script setup>
import { Moon, Sun } from 'lucide-vue-next';
import { useTheme } from '@/composables/useTheme';

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <button
        type="button"
        class="focus-ring group relative grid h-10 w-10 place-items-center rounded-xl border border-line bg-surface text-subtle transition hover:border-brand-400/50 hover:text-ink"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="toggleTheme"
    >
        <Sun
            class="absolute h-[18px] w-[18px] transition-all duration-300"
            :class="isDark ? 'rotate-90 scale-0 opacity-0' : 'rotate-0 scale-100 opacity-100 text-amber-500'"
        />
        <Moon
            class="absolute h-[18px] w-[18px] transition-all duration-300"
            :class="isDark ? 'rotate-0 scale-100 opacity-100 text-brand-300' : '-rotate-90 scale-0 opacity-0'"
        />
    </button>
</template>
