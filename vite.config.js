import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    // Bound to the same host the site is browsed on. Left to itself Vite listens on IPv6
    // ([::1]) while Laravel serves IPv4 (127.0.0.1), and the browser then treats the page and
    // its scripts as two different origins — which quietly costs you anything origin-scoped,
    // the camera permission on the check-in desk included.
    server: {
        host: '127.0.0.1',
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/scss/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
