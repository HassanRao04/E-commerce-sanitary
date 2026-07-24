/**
 * Cart quantity controls, offer/pipe selectors, and item removal via AJAX.
 */
export default function initCommerceCart() {
    const root = document.querySelector('[data-commerce-cart]');

    if (!root) {
        return;
    }

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

            await updateCartItem(root, item, { quantity }, input);
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
        const qtyInput = event.target.closest('[data-qty-input]');
        const offerSelect = event.target.closest('[data-cart-offer]');
        const pipeSelect = event.target.closest('[data-cart-pipe]');

        if (!qtyInput && !offerSelect && !pipeSelect) {
            return;
        }

        const item = event.target.closest('[data-cart-item]');

        if (!item) {
            return;
        }

        const quantityInput = item.querySelector('[data-qty-input]');
        let quantity = Number(quantityInput?.value || 1);
        quantity = Math.max(1, Math.min(99, quantity));

        if (quantityInput) {
            quantityInput.value = String(quantity);
        }

        const payload = { quantity };

        if (offerSelect) {
            payload.product_offer_id = offerSelect.value || '';
            const buyQty = Number(offerSelect.selectedOptions[0]?.dataset.buyQuantity || 1);
            quantity = Math.max(1, Math.min(99, buyQty || 1));
            payload.quantity = quantity;

            if (quantityInput) {
                quantityInput.value = String(quantity);
            }
        }

        if (pipeSelect) {
            payload.pipe_length_option_id = pipeSelect.value || '';
        }

        await updateCartItem(root, item, payload, quantityInput ?? event.target);
    });
}

async function updateCartItem(root, itemEl, payload, controlEl) {
    if (controlEl) {
        controlEl.disabled = true;
    }

    try {
        const body = new FormData();
        body.append('_method', 'PATCH');
        body.append('quantity', String(payload.quantity));

        if (Object.prototype.hasOwnProperty.call(payload, 'product_offer_id')) {
            body.append('product_offer_id', payload.product_offer_id);
        }

        if (Object.prototype.hasOwnProperty.call(payload, 'pipe_length_option_id')) {
            body.append('pipe_length_option_id', payload.pipe_length_option_id);
        }

        const response = await fetch(`/cart/items/${itemEl.dataset.cartItem}`, {
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
        syncCartUi(root, preview, itemEl.dataset.cartItem);
        document.dispatchEvent(new CustomEvent('storefront:cart-updated', { detail: preview }));
    } catch (error) {
        window.location.reload();
    } finally {
        if (controlEl) {
            controlEl.disabled = false;
        }
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

function syncCartUi(root, preview, itemId) {
    const item = root.querySelector(`[data-cart-item="${itemId}"]`);
    const line = preview.items.find((entry) => String(entry.id) === String(itemId));

    if (item && line) {
        const lineTotal = item.querySelector('[data-line-total]');
        const unitPrice = item.querySelector('[data-unit-price]');
        const input = item.querySelector('[data-qty-input]');
        const offerSelect = item.querySelector('[data-cart-offer]');
        const pipeSelect = item.querySelector('[data-cart-pipe]');

        if (lineTotal) {
            lineTotal.textContent = line.line_total_formatted;
        }

        if (unitPrice) {
            unitPrice.textContent = `${line.unit_price_formatted} each`;
        }

        if (input) {
            input.value = String(line.quantity);
        }

        if (offerSelect && line.product_offer_id !== undefined) {
            offerSelect.value = line.product_offer_id ? String(line.product_offer_id) : '';
        }

        if (pipeSelect && line.pipe_length_option_id) {
            pipeSelect.value = String(line.pipe_length_option_id);
        }
    }

    syncTotals(root, preview);
}

function syncTotals(root, preview) {
    const totals = preview.totals ?? {};
    const setText = (selector, value) => {
        const el = root.querySelector(selector);

        if (el && value !== undefined && value !== null) {
            el.replaceChildren(document.createTextNode(value));
        }
    };

    setText('[data-total-subtotal]', totals.subtotal_formatted);
    setText('[data-total-grand]', totals.grand_total_formatted);
    setText('[data-mobile-total]', totals.grand_total_formatted);

    const discountRow = root.querySelector('[data-discount-row]');
    const discountValue = Number(totals.discount ?? 0);

    if (discountRow) {
        discountRow.hidden = discountValue <= 0;
    }

    if (discountValue > 0 && totals.discount_formatted) {
        setText('[data-total-discount]', `- ${totals.discount_formatted}`);
    }

    const shippingEl = root.querySelector('[data-total-shipping]');

    if (shippingEl) {
        if (Number(totals.shipping ?? 0) <= 0 && totals.qualifies_for_free_shipping) {
            shippingEl.innerHTML = '<span class="order-summary__free">Free</span>';
        } else {
            shippingEl.replaceChildren(document.createTextNode(totals.shipping_formatted ?? ''));
        }
    }

    setText('[data-total-service-charge]', totals.service_charge_formatted);
    setText('[data-total-handling-charge]', totals.handling_charge_formatted);
    setText('[data-total-tax]', totals.tax_formatted);
}

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}
