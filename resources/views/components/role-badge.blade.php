@props(['role'])

@php
    $staffRole = $role instanceof \App\Enums\StaffRole
        ? $role
        : \App\Enums\StaffRole::tryFromName(is_string($role) ? $role : (string) $role);
@endphp

@if ($staffRole)
    <span {{ $attributes->merge([
        'class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.$staffRole->badgeClasses(),
    ]) }}>
        {{ $staffRole->label() }}
    </span>
@elseif (filled($role))
    <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600']) }}>
        {{ str_replace('-', ' ', ucfirst((string) $role)) }}
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 italic']) }}>
        No role
    </span>
@endif
