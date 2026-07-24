@extends('layouts.storefront')

@php
    $contactContent = $storefrontContact ?? \App\Support\StorefrontContact::resolved();
    $contactFormEnabled = $contactFormEnabled ?? \App\Models\SiteSetting::current()->isContactFormEnabled();
@endphp

@section('title', ($contactContent['page_title'] ?? 'Contact Us').' — '.(\App\Models\SiteSetting::current()->displayName()))
@section('meta_description', $contactContent['intro'] ?? 'Get in touch for product enquiries, quotes, and support.')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'Contact', 'url' => null],
        ]])

        @if (session('error'))
            <div class="mb-8 rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-rose-900" role="alert">
                <p class="font-semibold">{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid lg:grid-cols-2 gap-10">
            <div>
                <h1 class="text-3xl font-bold">{{ $contactContent['page_title'] ?? 'Contact us' }}</h1>
                <p class="text-slate-600 mt-3 leading-relaxed">{{ $contactContent['intro'] ?? '' }}</p>

                <dl class="mt-8 space-y-4 text-sm">
                    @if (filled($contactContent['email'] ?? null))
                        <div>
                            <dt class="font-medium text-slate-900">Email</dt>
                            <dd class="text-slate-600"><a href="mailto:{{ $contactContent['email'] }}" class="hover:underline">{{ $contactContent['email'] }}</a></dd>
                        </div>
                    @endif
                    @if (filled($contactContent['phone'] ?? null))
                        <div>
                            <dt class="font-medium text-slate-900">Phone</dt>
                            <dd class="text-slate-600">{{ $contactContent['phone'] }}</dd>
                        </div>
                    @endif
                    @if (filled($contactContent['whatsapp'] ?? null))
                        <div>
                            <dt class="font-medium text-slate-900">WhatsApp</dt>
                            <dd class="text-slate-600">
                                @php($contactWhatsappUrl = \App\Support\SocialLinks::whatsappUrl())
                                @if ($contactWhatsappUrl)
                                    <a href="{{ $contactWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                        {{ $contactContent['whatsapp'] }}
                                    </a>
                                @else
                                    {{ $contactContent['whatsapp'] }}
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if (filled($contactContent['address'] ?? null))
                        <div>
                            <dt class="font-medium text-slate-900">Address</dt>
                            <dd class="text-slate-600">{{ $contactContent['address'] }}</dd>
                        </div>
                    @endif
                    @if (filled($contactContent['business_hours'] ?? null))
                        <div>
                            <dt class="font-medium text-slate-900">Business hours</dt>
                            <dd class="text-slate-600">{{ $contactContent['business_hours'] }}</dd>
                        </div>
                    @endif
                    @if ($contactContent['show_order_tracking'] ?? true)
                        <div>
                            <dt class="font-medium text-slate-900">Order tracking</dt>
                            <dd class="text-slate-600"><a href="{{ route('shop.orders.track') }}" class="hover:underline">{{ $contactContent['order_tracking_label'] ?? 'Track your order online' }}</a></dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if ($contactFormEnabled)
                <form action="{{ route('shop.contact.store') }}" method="POST" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium mb-1">Full name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required class="w-full rounded-lg border-slate-300">
                        @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="contact-email" class="block text-sm font-medium mb-1">Email</label>
                        <input id="contact-email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required class="w-full rounded-lg border-slate-300">
                        @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium mb-1">Phone (optional)</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" class="w-full rounded-lg border-slate-300">
                        @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="subject" class="block text-sm font-medium mb-1">Subject</label>
                        <input id="subject" type="text" name="subject" value="{{ old('subject') }}" required class="w-full rounded-lg border-slate-300">
                        @error('subject')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium mb-1">Message</label>
                        <textarea id="message" name="message" rows="5" required class="w-full rounded-lg border-slate-300">{{ old('message') }}</textarea>
                        @error('message')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-slate-900 text-white py-3 font-semibold hover:bg-slate-800">Send message</button>
                </form>
            @else
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Form temporarily unavailable</h2>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                        Online inquiries are currently disabled. Please contact us using the phone, email, or WhatsApp details listed on this page.
                    </p>
                </div>
            @endif
        </div>
    </div>
@endsection
