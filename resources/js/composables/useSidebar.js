import { computed, ref } from 'vue';

const STORAGE_KEY = 'lumora-sidebar';
const collapsed = ref(false);
let initialized = false;

export function initSidebar() {
    if (initialized || typeof window === 'undefined') return;
    initialized = true;

    try {
        collapsed.value = window.localStorage.getItem(STORAGE_KEY) === 'collapsed';
    } catch {
        collapsed.value = false;
    }
}

export function useSidebar() {
    const toggle = () => {
        collapsed.value = !collapsed.value;

        try {
            window.localStorage.setItem(STORAGE_KEY, collapsed.value ? 'collapsed' : 'expanded');
        } catch {
            // Persistence is optional.
        }
    };

    const collapse = () => {
        if (!collapsed.value) toggle();
    };

    const expand = () => {
        if (collapsed.value) toggle();
    };

    return {
        collapsed: computed(() => collapsed.value),
        toggle,
        collapse,
        expand,
    };
}
