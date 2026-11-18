<script setup>
import { CheckCircle2, Info, TriangleAlert, X, XCircle } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';

const { toasts, dismiss } = useToast();

const icons = {
    success: CheckCircle2,
    error: XCircle,
    warning: TriangleAlert,
    info: Info,
};

const tones = {
    success: 'text-success',
    error: 'text-danger',
    warning: 'text-pending',
    info: 'text-info',
};
</script>

<template>
    <div class="pointer-events-none fixed inset-x-4 bottom-4 z-[95] flex flex-col items-end gap-2 sm:inset-x-auto sm:right-6 sm:bottom-6 sm:w-[380px]" aria-live="polite" aria-atomic="false">
        <TransitionGroup
            tag="div"
            class="flex w-full flex-col gap-2"
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-3 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex w-full items-start gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lift backdrop-blur-xl"
            >
                <component :is="icons[toast.type] ?? icons.info" :class="['mt-0.5 h-5 w-5 shrink-0', tones[toast.type] ?? tones.info]" />

                <div class="min-w-0 flex-1">
                    <p class="text-[13.5px] font-semibold leading-tight text-ink">{{ toast.title }}</p>
                    <p v-if="toast.message" class="mt-1 text-[12.5px] leading-relaxed text-subtle">{{ toast.message }}</p>
                </div>

                <button
                    type="button"
                    class="focus-ring -mr-1 grid h-6 w-6 shrink-0 place-items-center rounded-md text-subtle/70 transition hover:bg-elevated hover:text-ink"
                    aria-label="Dismiss notification"
                    @click="dismiss(toast.id)"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
