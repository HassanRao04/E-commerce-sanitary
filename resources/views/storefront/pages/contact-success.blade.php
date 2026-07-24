@extends('layouts.storefront')

@section('title', 'Inquiry Received — Inayat Sons Sanitary Ware')
@section('meta_description', 'Your customer inquiry has been received successfully.')

@section('content')
    <div class="mx-auto flex min-h-[60vh] max-w-3xl items-center px-4 py-12 sm:px-6 lg:px-8">
        <section class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/5" role="status">
            <div class="bg-gradient-to-br from-emerald-50 to-white px-6 py-10 text-center sm:px-12">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 ring-8 ring-emerald-50">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                    </svg>
                </div>

                <h1 class="mt-6 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Thank you for contacting Inayat Sons Sanitary Ware.
                </h1>
                <p class="mx-auto mt-3 max-w-xl text-base leading-relaxed text-slate-600">
                    Your inquiry has been received successfully.
                </p>

                <div class="mx-auto mt-7 max-w-sm rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Reference ID</p>
                    <p class="mt-1 text-xl font-bold tracking-wide text-slate-900">{{ $referenceId }}</p>
                </div>

                <p class="mt-4 text-sm text-slate-500">Please keep this reference for future communication.</p>

                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('shop.products.index') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Continue Shopping
                    </a>

                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Chat on WhatsApp
                        </a>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
