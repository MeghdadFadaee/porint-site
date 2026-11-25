import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [laravel({
        input: ['resources/css/studio.css', 'resources/js/preview.ts'],
        refresh: true,
    })],
});
