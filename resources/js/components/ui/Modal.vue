<script setup>
import { computed, onBeforeUnmount, onMounted, ref, useId } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    size: { type: String, default: 'md' },
    closeable: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const panel = ref(null);
const id = useId();
const titleId = computed(() => `modal-title-${id}`);
const sizes = {
    sm: 'sm:max-w-md',
    md: 'sm:max-w-xl',
    lg: 'sm:max-w-3xl',
    xl: 'sm:max-w-5xl',
    full: 'sm:max-w-6xl',
};

let previousOverflow = '';

const onKeydown = (event) => {
    if (event.key === 'Escape' && props.closeable) {
        emit('close');
    }
};

onMounted(() => {
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', onKeydown);
    panel.value?.focus();
});

onBeforeUnmount(() => {
    document.body.style.overflow = previousOverflow;
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-[80] flex items-end justify-center sm:items-center sm:p-6">
            <div class="absolute inset-0 animate-fade bg-midnight/65 backdrop-blur-[3px]" aria-hidden="true" @click="closeable && emit('close')" />

            <div
                ref="panel"
                tabindex="-1"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="title ? titleId : undefined"
                :class="[
                    'relative z-10 flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-3xl border border-line bg-surface shadow-2xl animate-scale-in sm:rounded-3xl',
                    sizes[size] ?? sizes.md,
                ]"
            >
                <header v-if="title || $slots.header" class="flex items-start justify-between gap-4 border-b border-line/80 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <slot name="header">
                            <h2 :id="titleId" class="text-lg font-semibold leading-tight">{{ title }}</h2>
                            <p v-if="description" class="mt-1 text-[13px] leading-relaxed text-subtle">{{ description }}</p>
                        </slot>
                    </div>

                    <button
                        v-if="closeable"
                        type="button"
                        class="focus-ring -mr-1 grid h-9 w-9 shrink-0 place-items-center rounded-lg text-subtle transition hover:bg-elevated hover:text-ink"
                        aria-label="Close dialog"
                        @click="emit('close')"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </header>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <slot />
                </div>

                <footer v-if="$slots.footer" class="border-t border-line/80 bg-muted/40 px-5 py-4 sm:px-6">
                    <slot name="footer" />
                </footer>
            </div>
        </div>
    </Teleport>
</template>
