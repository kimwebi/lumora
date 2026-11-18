import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { initTheme } from '@/composables/useTheme';

const appName = 'Lumora';

createInertiaApp({
    // Leave the server-rendered <title> in place until the page or useSeo()
    // supplies one; returning a brand fallback here would overwrite it on boot.
    title: (title) => (title ? `${title} · ${appName}` : ''),
    resolve: (name) => {
        const pages = import.meta.glob('./features/**/pages/*.vue');

        if (!pages[`./${name}.vue`]) {
            throw new Error(`Lumora page not found: ${name}`);
        }

        return pages[`./${name}.vue`]();
    },
    setup({ el, App, props, plugin }) {
        initTheme();

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#7C3AED',
        showSpinner: false,
    },
});
