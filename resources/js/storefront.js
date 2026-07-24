import './bootstrap';

import Alpine from 'alpinejs';
import storefrontHeader from './storefront/header';
import productCard from './storefront/product-card';
import storefrontQuickView from './storefront/quick-view';
import shopCatalog from './storefront/shop';
import { productGallery, productPurchase } from './storefront/product-page';
import initCommerceCart from './storefront/cart-checkout';

window.Alpine = Alpine;
Alpine.data('storefrontHeader', storefrontHeader);
Alpine.data('productCard', productCard);
Alpine.data('storefrontQuickView', storefrontQuickView);
Alpine.data('shopCatalog', shopCatalog);
Alpine.data('productGallery', productGallery);
Alpine.data('productPurchase', productPurchase);

Alpine.start();

function whenIdle(callback, timeout = 1800) {
    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(callback, { timeout });
        return;
    }

    window.setTimeout(callback, 1);
}

async function loadSwiperStyles() {
    await import('./storefront/swiper-styles');
}

document.addEventListener('DOMContentLoaded', () => {
    initCommerceCart();

    // Critical above-the-fold: hero
    if (document.querySelector('[data-hero-swiper]')) {
        Promise.all([loadSwiperStyles(), import('./storefront/hero-swiper')]).then(([, mod]) => {
            mod.default();
        });
    }

    // Below-fold / conditional: defer with idle so main thread stays free for LCP
    whenIdle(() => {
        const boot = async () => {
            const needsSwiper =
                document.querySelector('[data-product-carousel]') ||
                document.querySelector('[data-testimonials]');

            if (needsSwiper) {
                await loadSwiperStyles();
            }

            if (document.querySelector('[data-product-carousel]')) {
                const { default: initProductCarousels } = await import('./storefront/product-carousel');
                initProductCarousels();
            }

            if (document.querySelector('[data-testimonials]')) {
                const { default: initTestimonials } = await import('./storefront/testimonials');
                initTestimonials();
            }

            if (document.querySelector('.ds-root')) {
                const { initStorefrontAnimations } = await import('./storefront/animations');
                initStorefrontAnimations();
            }
        };

        boot().catch((error) => console.error(error));
    });
});
