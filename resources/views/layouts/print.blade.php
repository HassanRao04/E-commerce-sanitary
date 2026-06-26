<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Print')</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; color: #111827; margin: 0; padding: 24px; font-size: 14px; }
        h1, h2, h3 { margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 0; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .muted { color: #6b7280; }
        .text-right { text-align: right; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .totals { margin-top: 16px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .grand { font-weight: 700; font-size: 18px; border-top: 2px solid #111827; margin-top: 8px; padding-top: 8px; }
        .label-box { border: 2px dashed #111827; padding: 24px; max-width: 420px; }
        .barcode { font-family: monospace; letter-spacing: 2px; font-size: 18px; margin: 12px 0; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
    @stack('head')
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer;">Print</button>
        <button onclick="window.close()" style="padding: 8px 16px; cursor: pointer; margin-left: 8px;">Close</button>
    </div>
    @yield('content')
</body>
</html>
