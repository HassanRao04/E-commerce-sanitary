export default function headerNavEditor(initialItems = []) {
    return {
        items: initialItems.map((item, index) => ({
            label: item.label ?? '',
            route: item.route ?? '',
            url: item.url ?? '',
            active_patterns: Array.isArray(item.active_patterns)
                ? item.active_patterns.join(', ')
                : (item.active_patterns ?? ''),
            enabled: item.enabled ?? true,
            sort_order: item.sort_order ?? index * 10,
            mega_menu: item.mega_menu ?? false,
            open_in_new_tab: item.open_in_new_tab ?? false,
        })),

        addItem() {
            this.items.push({
                label: '',
                route: '',
                url: '',
                active_patterns: '',
                enabled: true,
                sort_order: (this.items.length + 1) * 10,
                mega_menu: false,
                open_in_new_tab: false,
            });
        },

        removeItem(index) {
            if (this.items.length <= 1) {
                return;
            }

            this.items.splice(index, 1);
        },

        moveItem(index, direction) {
            const target = index + direction;

            if (target < 0 || target >= this.items.length) {
                return;
            }

            const [item] = this.items.splice(index, 1);
            this.items.splice(target, 0, item);
            this.items.forEach((entry, position) => {
                entry.sort_order = position * 10;
            });
        },
    };
}
