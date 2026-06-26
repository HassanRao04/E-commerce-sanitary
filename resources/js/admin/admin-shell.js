/**
 * Admin layout shell: sidebar drawer (mobile) + collapsible sidebar (desktop).
 * Persists desktop collapsed state in localStorage.
 */
export default function adminShell(storageKey = 'admin_sidebar_collapsed') {
    return {
        mobileOpen: false,
        profileOpen: false,
        sidebarCollapsed: localStorage.getItem(storageKey) === 'true',
        isDesktop: false,

        init() {
            const media = window.matchMedia('(min-width: 1024px)');

            const syncViewport = () => {
                this.isDesktop = media.matches;
                if (this.isDesktop) {
                    this.mobileOpen = false;
                }
            };

            syncViewport();
            media.addEventListener('change', syncViewport);
        },

        toggleSidebar() {
            if (this.isDesktop) {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem(storageKey, this.sidebarCollapsed ? 'true' : 'false');
                return;
            }

            this.mobileOpen = !this.mobileOpen;
        },

        closeMobileSidebar() {
            this.mobileOpen = false;
        },

        shellClasses() {
            return {
                'admin-sidebar-collapsed': this.isDesktop && this.sidebarCollapsed,
                'admin-mobile-open': !this.isDesktop && this.mobileOpen,
            };
        },
    };
}
