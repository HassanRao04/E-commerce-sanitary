@extends('layouts.storefront')

@section('title', 'Contact Us — '.config('app.name'))
@section('meta_description', 'Get in touch with '.config('app.name').' for product enquiries, quotes, and support.')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'Contact', 'url' => null],
        ]])

        <div class="grid lg:grid-cols-2 gap-10">
            <div>
                <h1 class="text-3xl font-bold">Contact us</h1>
                <p class="text-slate-600 mt-3 leading-relaxed">Have a question about a product, need a quote, or want help with an order? Send us a message and we'll get back to you within one business day.</p>

                <dl class="mt-8 space-y-4 text-sm">
                    <div>
                        <dt class="font-medium text-slate-900">Email</dt>
                        <dd class="text-slate-600"><a href="mailto:{{ config('shop.admin_email') }}" class="hover:underline">{{ config('shop.admin_email') }}</a></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-900">Business hours</dt>
                        <dd class="text-slate-600">Monday – Saturday, 9:00 AM – 6:00 PM (PKT)</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-900">Order tracking</dt>
                        <dd class="text-slate-600"><a href="{{ route('shop.orders.track') }}" class="hover:underline">Track your order online</a></dd>
                    </div>
                </dl>
            </div>

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
        </div>
    </div>
@endsection
