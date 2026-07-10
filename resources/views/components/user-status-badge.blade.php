@props(['status'])

@php
    $userStatus = $status instanceof \App\Enums\UserStatus
        ? $status
        : \App\Enums\UserStatus::tryFrom((string) $status);
@endphp

@if ($userStatus)
    <span {{ $attributes->merge([
        'class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.$userStatus->badgeClasses(),
    ]) }}>
        {{ $userStatus->label() }}
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600']) }}>
        Unknown
    </span>
@endif
