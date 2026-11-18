import { computed, readonly, ref } from 'vue';

const STORAGE_KEY = 'lumora-theme';
const PREFERENCES = ['light', 'dark', 'system'];

const preference = ref('system');
const systemDark = ref(false);
let mediaQuery = null;
let initialized = false;

const resolved = computed(() =>
    preference.value === 'system' ? (systemDark.value ? 'dark' : 'light') : preference.value,
);

const isDark = computed(() => resolved.value === 'dark');

function apply() {
    if (typeof document === 'undefined') return;

    const root = document.documentElement;
    root.classList.toggle('dark', isDark.value);
    root.style.colorScheme = resolved.value;

    const themeMeta = document.querySelector('meta[name="theme-color"]');
    if (themeMeta) {
        themeMeta.setAttribute('content', isDark.value ? '#09090F' : '#F8FAFC');
    }
}

export function initTheme() {
    if (initialized || typeof window === 'undefined') return;

    initialized = true;

    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);
        preference.value = PREFERENCES.includes(stored) ? stored : 'system';
    } catch {
        preference.value = 'system';
    }

    mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    systemDark.value = mediaQuery.matches;

    mediaQuery.addEventListener('change', (event) => {
        systemDark.value = event.matches;
        if (preference.value === 'system') apply();
    });

    apply();
}

export function setTheme(value) {
    if (!PREFERENCES.includes(value)) return;

    preference.value = value;

    try {
        window.localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // Persisting the theme is optional.
    }

    apply();
}

export function toggleTheme() {
    setTheme(isDark.value ? 'light' : 'dark');
}

export function useTheme() {
    return {
        preference: readonly(preference),
        resolved,
        isDark,
        setTheme,
        toggleTheme,
    };
}
