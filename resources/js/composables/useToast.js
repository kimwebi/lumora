import { readonly, ref } from 'vue';

let counter = 0;
const toasts = ref([]);
const timers = new Map();

function dismiss(id) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);

    if (timers.has(id)) {
        clearTimeout(timers.get(id));
        timers.delete(id);
    }
}

function push(options) {
    const id = ++counter;
    const toast = {
        id,
        type: 'success',
        title: '',
        message: '',
        duration: 4800,
        ...options,
    };

    toasts.value = [...toasts.value, toast].slice(-4);

    if (typeof window !== 'undefined' && toast.duration > 0) {
        timers.set(
            id,
            window.setTimeout(() => dismiss(id), toast.duration),
        );
    }

    return id;
}

export function useToast() {
    return {
        toasts: readonly(toasts),
        toast: push,
        dismiss,
        success: (message, title = 'Success') => push({ type: 'success', title, message }),
        error: (message, title = 'Something went wrong') => push({ type: 'error', title, message, duration: 6500 }),
        info: (message, title = 'Heads up') => push({ type: 'info', title, message }),
        warning: (message, title = 'Take a look') => push({ type: 'warning', title, message, duration: 6000 }),
    };
}
