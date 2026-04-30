import { createInertiaApp } from '@inertiajs/svelte';
import { hydrate, mount } from 'svelte';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.svelte', { eager: true });
        return pages[`./pages/${name}.svelte`];
    },
    setup({ el, App, props }) {
        hydrate(App, { target: el, props });
    },

    progress: {
        color: '#0078D4',
    },
});
