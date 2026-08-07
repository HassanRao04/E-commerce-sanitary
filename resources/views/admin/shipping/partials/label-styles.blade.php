<style>
    .label-toolbar {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }

    .label-toolbar button,
    .label-format-link {
        padding: 6px 12px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        background: #fff;
        color: #111827;
        text-decoration: none;
        font-size: 12px;
        cursor: pointer;
    }

    .label-format-link.is-active {
        background: #111827;
        border-color: #111827;
        color: #fff;
    }

    .label-toolbar-divider {
        color: #9ca3af;
    }

    .shipping-label {
        border: 1px solid #111827;
        background: #fff;
        color: #111827;
        padding: 10px 12px;
        font-size: 11px;
        line-height: 1.4;
        max-width: 148mm;
    }

    .shipping-label--thermal {
        width: 4in;
        max-width: 4in;
        padding: 8px;
        font-size: 9px;
    }

    .shipping-label__block {
        padding: 8px 0;
        border-bottom: 1px dashed #9ca3af;
    }

    .shipping-label__block:first-child {
        padding-top: 0;
    }

    .shipping-label__block:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .shipping-label__block--store {
        text-align: center;
    }

    .shipping-label__logo {
        width: 48px;
        height: 48px;
        object-fit: contain;
        margin: 0 auto 6px;
        display: block;
    }

    .shipping-label--thermal .shipping-label__logo {
        width: 36px;
        height: 36px;
    }

    .shipping-label__store-name {
        margin: 0 0 4px;
        font-size: 13px;
        font-weight: 700;
    }

    .shipping-label--thermal .shipping-label__store-name {
        font-size: 11px;
    }

    .shipping-label__heading {
        margin: 0 0 5px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    .shipping-label__heading--qr {
        margin-top: 10px;
    }

    .shipping-label__line {
        margin: 0 0 2px;
    }

    .shipping-label__line--name {
        font-weight: 700;
        font-size: 12px;
    }

    .shipping-label--thermal .shipping-label__line--name {
        font-size: 10px;
    }

    .shipping-label__row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 10px;
        margin: 0 0 3px;
    }

    .shipping-label__row-label {
        color: #374151;
        flex-shrink: 0;
    }

    .shipping-label__row-label--upper {
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-size: 10px;
    }

    .shipping-label__row--amount {
        font-weight: 600;
    }

    .shipping-label__row--cod {
        font-weight: 700;
    }

    .shipping-label__product-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 8px;
        margin: 0 0 3px;
    }

    .shipping-label__product-name {
        flex: 1;
        min-width: 0;
    }

    .shipping-label__product-qty {
        flex-shrink: 0;
        font-weight: 600;
        white-space: nowrap;
    }

    .shipping-label__mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        letter-spacing: 0.02em;
    }

    .shipping-label__block--bottom {
        text-align: center;
    }

    .shipping-label__barcode {
        width: 100%;
        max-width: 100%;
        height: 48px;
        display: block;
        margin: 0 auto;
    }

    .shipping-label--thermal .shipping-label__barcode {
        height: 38px;
    }

    .shipping-label__tracking-no {
        margin: 4px 0 0;
        font-size: 10px;
        font-weight: 700;
    }

    .shipping-label--thermal .shipping-label__tracking-no {
        font-size: 8px;
    }

    .shipping-label__qrcode {
        width: 80px;
        height: 80px;
        display: block;
        margin: 4px auto 0;
    }

    .shipping-label--thermal .shipping-label__qrcode {
        width: 68px;
        height: 68px;
    }

    @media print {
        body {
            padding: 0;
            margin: 0;
        }

        .shipping-label {
            border: 1px solid #111827;
            margin: 0 auto;
        }

        .shipping-label--a4 {
            width: 148mm;
            max-width: 148mm;
        }

        .shipping-label--thermal {
            width: 4in;
            max-width: 4in;
        }
    }

    @media print {
        body:has(.shipping-label--a4) {
            width: 148mm;
        }
    }

    @page a4-label {
        size: A5 portrait;
        margin: 8mm;
    }

    @page thermal-label {
        size: 4in 6in portrait;
        margin: 0.1in;
    }

    @media print {
        .shipping-label--a4 {
            page: a4-label;
        }

        .shipping-label--thermal {
            page: thermal-label;
        }
    }
</style>
