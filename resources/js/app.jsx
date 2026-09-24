import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { isLocalOnlyHost } from './utils/queueUrl';

// Live updates (Reverb). On the network, connect through the same address the page was opened
// with (Apache forwards /app to Reverb), so a new IP address needs no rebuild. Opened as
// queue-system.test on the development PC, use the VITE_REVERB_* values from .env instead.
if (isLocalOnlyHost()) {
    configureEcho({ broadcaster: 'reverb' });
} else {
    const port = Number(window.location.port) || (window.location.protocol === 'https:' ? 443 : 80);

    configureEcho({
        broadcaster: 'reverb',
        wsHost: window.location.hostname,
        wsPort: port,
        wssPort: port,
        forceTLS: window.location.protocol === 'https:',
    });
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#566f30',
    },
});
