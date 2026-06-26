/**
 * Cart quantity controls and item removal via AJAX.
 */
export default function initCommerceCart() {
    const root = document.querySelector('[data-commerce-cart]');

    if (!root) {
        return;
    }

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    root.addEventListener('click', async (event) => {
        const decrease = event.target.closest('[data-qty-decrease]');
        const increase = event.target.closest('[data-qty-increase]');
        const remove = event.target.closest('[data-remove-item]');

        if (decrease || increase) {
            const control = event.target.closest('[data-qty-control]');
            const input = control?.querySelector('[data-qty-input]');
            const item = event.target.closest('[data-cart-item]');

            if (!input || !item) {
                return;
            }

            event.preventDefault();

            let quantity = Number(input.value || 1);

            if (decrease) {
                quantity = Math.max(1, quantity - 1);
            } else {
                quantity = Math.min(99, quantity + 1);
            }

            await updateCartItem(root, item.dataset.cartItem, quantity, input);
        }

        if (remove) {
            event.preventDefault();
            const item = event.target.closest('[data-cart-item]');

            if (!item) {
                return;
            }

            await removeCartItem(root, item);
        }
    });

    root.addEventListener('change', async (event) => {
        const input = event.target.closest('[data-qty-input]');

        if (!input) {
            return;
        }

        const item = input.closest('[data-cart-item]');

        if (!item) {
            return;
        }

        let quantity = Number(input.value || 1);
        quantity = Math.max(1, Math.min(99, quantity));
        input.value = String(quantity);

        await updateCartItem(root, item.dataset.cartItem, quantity, input);
    });
}

async function updateCartItem(root, itemId, quantity, input) {
    input.disabled = true;

    try {
        const body = new FormData();
        body.append('_method', 'PATCH');
        body.append('quantity', String(quantity));

        const response = await fetch(`/cart/items/${itemId}`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf(),
            },
            body,
        });

        if (!response.ok) {
            throw new Error('Cart update failed');
        }

        const preview = await response.json();
        syncCartUi(root, preview, itemId, quantity);
        document.dispatchEvent(new CustomEvent('storefront:cart-updated', { detail: preview }));
    } catch (error) {
        window.location.reload();
    } finally {
        input.disabled = false;
    }
}

async function removeCartItem(root, itemEl) {
    const itemId = itemEl.dataset.cartItem;

    try {
        const response = await fetch(`/cart/items/${itemId}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf(),
            },
        });

        if (!response.ok) {
            throw new Error('Cart remove failed');
        }

        const preview = await response.json();

        if (preview.count === 0) {
            window.location.reload();

            return;
        }

        itemEl.remove();
        syncTotals(root, preview);
        document.dispatchEvent(new CustomEvent('storefront:cart-updated', { detail: preview }));
    } catch (error) {
        window.location.reload();
    }
}

function syncCartUi(root, preview, itemId, quantity) {
    const item = root.querySelector(`[data-cart-item="${itemId}"]`);

    if (item) {
        const line = preview.items.find((entry) => String(entry.id) === String(itemId));
        const lineTotal = item.querySelector('[data-line-total]');

        if (line && lineTotal) {
            lineTotal.textContent = line.line_total_formatted;
        }

        const input = item.querySelector('[data-qty-input]');

        if (input) {
            input.value = String(quantity);
        }
    }

    syncTotals(root, preview);
}

function syncTotals(root, preview) {
    const totals = preview.totals ?? {};

    root.querySelector('[data-total-subtotal]')?.replaceChildren(document.createTextNode(totals.subtotal_formatted ?? ''));
    root.querySelector('[data-total-grand]')?.replaceChildren(document.createTextNode(totals.grand_total_formatted ?? ''));
    root.querySelector('[data-mobile-total]')?.replaceChildren(document.createTextNode(totals.grand_total_formatted ?? ''));

    if (totals.discount_formatted) {
        root.querySelector('[data-total-discount]')?.replaceChildren(document.createTextNode(`- ${totals.discount_formatted}`));
    }
}
