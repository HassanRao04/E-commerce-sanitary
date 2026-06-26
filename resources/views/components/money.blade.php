@props(['amount'])

<span {{ $attributes }}>{{ config('shop.currency_symbol') }} {{ number_format((float) $amount, 2) }}</span>
