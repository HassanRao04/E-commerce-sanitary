import './bootstrap';

import Alpine from 'alpinejs';
import adminShell from './admin/admin-shell';
import storefrontHeader from './storefront/header';
import productCard from './storefront/product-card';
import storefrontQuickView from './storefront/quick-view';
import initHeroSwiper from './storefront/hero-swiper';
import initProductCarousels from './storefront/product-carousel';
import initTestimonials from './storefront/testimonials';
import shopCatalog from './storefront/shop';
import { productGallery, productPurchase } from './storefront/product-page';
import initCommerceCart from './storefront/cart-checkout';
import { initStorefrontAnimations } from './storefront/animations';
import './admin/product-form';

window.Alpine = Alpine;
Alpine.data('adminShell', adminShell);
Alpine.data('storefrontHeader', storefrontHeader);
Alpine.data('productCard', productCard);
Alpine.data('storefrontQuickView', storefrontQuickView);
Alpine.data('shopCatalog', shopCatalog);
Alpine.data('productGallery', productGallery);
Alpine.data('productPurchase', productPurchase);

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initHeroSwiper();
    initProductCarousels();
    initTestimonials();
    initCommerceCart();
    initStorefrontAnimations();
});
