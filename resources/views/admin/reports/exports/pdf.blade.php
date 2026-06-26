<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['label'] }} — {{ config('app.name') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        p { margin: 0 0 12px; color: #555; }
        .summary { margin-bottom: 16px; }
        .summary span { display: inline-block; margin-right: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ $meta['label'] }}</h1>
    <p>{{ $meta['description'] }} · Generated {{ now()->format('M j, Y H:i') }}@if($from && $to) · {{ $from }} to {{ $to }}@endif</p>

    <div class="summary">
        @foreach ($report['summary'] as $label => $value)
            <span><strong>{{ str($label)->headline() }}:</strong> {{ is_float($value) ? number_format($value, 2) : number_format((int) $value) }}</span>
        @endforeach
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ is_numeric($cell) && ! is_int($cell) ? number_format((float) $cell, 2) : $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}">No data available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
