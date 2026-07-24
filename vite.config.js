import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/storefront.css',
                'resources/js/storefront.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        cssCodeSplit: true,
        sourcemap: false,
        reportCompressedSize: false,
        chunkSizeWarningLimit: 700,
        rollupOptions: {
            output: {
                // Keep Alpine/vendor naturally split via dynamic imports in storefront.js
                manualChunks(id) {
                    if (id.includes('node_modules/swiper')) {
                        return 'swiper';
                    }

                    if (id.includes('node_modules/gsap') || id.includes('node_modules/aos')) {
                        return 'motion';
                    }

                    return undefined;
                },
            },
        },
    },
});
