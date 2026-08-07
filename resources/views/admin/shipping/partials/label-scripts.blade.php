<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var barcodeValue = @json($barcodeValue);
        var scanPayload = @json($scanPayload);
        var isThermal = @json($format === 'thermal');

        var barcodeEl = document.getElementById('shipping-label-barcode');
        if (barcodeEl && typeof JsBarcode !== 'undefined') {
            JsBarcode(barcodeEl, barcodeValue, {
                format: 'CODE128',
                displayValue: false,
                margin: 0,
                width: isThermal ? 1.2 : 1.6,
                height: isThermal ? 44 : 56,
            });
        }

        var qrEl = document.getElementById('shipping-label-qrcode');
        if (qrEl && typeof QRCode !== 'undefined') {
            QRCode.toCanvas(qrEl, scanPayload, {
                width: isThermal ? 80 : 96,
                margin: 1,
            });
        }
    });
</script>
