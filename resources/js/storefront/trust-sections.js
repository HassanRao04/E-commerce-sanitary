/**
 * Scroll-triggered reveal animations for trust and testimonial sections.
 */
export function initTrustSections() {
    const roots = document.querySelectorAll('[data-trust-sections], [data-testimonials], [data-newsletter]');

    if (roots.length === 0) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        roots.forEach((root) => {
            root.querySelectorAll('.trust-reveal').forEach((el) => el.classList.add('is-visible'));
        });

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.15 },
    );

    roots.forEach((root) => {
        root.querySelectorAll('.trust-reveal').forEach((el) => observer.observe(el));
    });
}
