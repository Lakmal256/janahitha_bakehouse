import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/standalone/base.css',
                'resources/css/standalone/orders.css',
                'resources/css/standalone/reports.css',
                'resources/css/standalone/admin.css',
                'resources/css/standalone/account-dashboard.css',
                'resources/js/standalone/app.js',
            ],
            refresh: true,
        }),
    ],
});
