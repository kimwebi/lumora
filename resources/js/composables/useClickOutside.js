import { onBeforeUnmount, onMounted } from 'vue';

export function useClickOutside(elementRef, callback) {
    const handler = (event) => {
        const element = elementRef.value;

        if (element && !element.contains(event.target)) {
            callback(event);
        }
    };

    onMounted(() => document.addEventListener('pointerdown', handler, true));
    onBeforeUnmount(() => document.removeEventListener('pointerdown', handler, true));
}
