import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-tenancy',
            input: [
                `${import.meta.dirname}/resources/js/app.ts`,
            ],
            refresh: true,
        }),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],

    build: {
        outDir: '../../public/build-tenancy',
        emptyOutDir: true,
        manifest: true,
    },

    resolve: {
        alias: {
            '@': `${import.meta.dirname}/resources/js`,
        },
    },
})