import { createInertiaApp } from '@inertiajs/svelte';
import createServer from '@inertiajs/svelte/server';
import { render } from 'svelte/server';

createServer(
    (page) =>
        createInertiaApp({
            page,
            render,
            resolve: (name) => {
                const pages = import.meta.glob('./pages/**/*.svelte', { eager: true });
                return pages[`./pages/${name}.svelte`];
            },
            setup({ App, props }) {
                return render(App, { props });
            },
        }),
    { cluster: true },
);
