@props(['user', 'size' => 'md'])

@php
    $sizeClasses = match ($size) {
        'sm' => 'h-10 w-10 text-sm',
        'md' => 'h-16 w-16 text-base',
        'lg' => 'h-24 w-24 text-2xl',
        'xl' => 'h-32 w-32 text-3xl',
        default => 'h-16 w-16 text-base',
    };
@endphp

@if ($user->profile_photo_url)
    <img
        {{ $attributes->merge([
            'src' => $user->profile_photo_url,
            'alt' => $user->full_name,
            'class' => 'rounded-full object-cover ring-2 ring-white shadow-sm '.$sizeClasses,
        ]) }}
    />
@else
    <div
        {{ $attributes->merge([
            'class' => 'inline-flex shrink-0 items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-600 ring-2 ring-white shadow-sm '.$sizeClasses,
        ]) }}
        aria-hidden="true"
    >
        {{ $user->initials }}
    </div>
@endif
