<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useClickOutside } from '@/composables/useClickOutside';

defineProps({
    align: { type: String, default: 'end' },
    width: { type: String, default: 'w-60' },
});

const open = ref(false);
const root = ref(null);

const close = () => {
    open.value = false;
};

const toggle = () => {
    open.value = !open.value;
};

const onKeydown = (event) => {
    if (event.key === 'Escape') close();
};

useClickOutside(root, close);

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div ref="root" class="relative">
        <div @click="toggle">
            <slot name="trigger" :open="open" />
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1 scale-[0.98]"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-1 scale-[0.98]"
        >
            <div
                v-if="open"
                :class="[
                    'absolute top-full z-50 mt-2 overflow-hidden rounded-2xl border border-line bg-surface p-1.5 shadow-lift',
                    width,
                    align === 'end' ? 'right-0' : 'left-0',
                ]"
                role="menu"
                @click="close"
            >
                <slot :close="close" />
            </div>
        </Transition>
    </div>
</template>
