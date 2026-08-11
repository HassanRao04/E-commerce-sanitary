@props(['at', 'format' => 'M j, Y g:i A'])

<span {{ $attributes }}>{{ \App\Support\AdminDateTime::format($at, $format) ?? '—' }}</span>
