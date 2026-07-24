@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Site Settings'])

    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl space-y-6">
        @csrf @method('PATCH')

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">General</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700">Site Name</label>
                <input type="text" name="site_name" value="{{ old('site_name', $settings->site_name) }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <textarea name="address" rows="2" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('address', $settings->address) }}</textarea>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <div>
                <h3 class="font-semibold text-gray-900">Contact &amp; support</h3>
                <p class="mt-1 text-sm text-gray-500">Business contact details used on the storefront and for customer inquiry notifications.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Business Email</label>
                    <input type="email" name="email" value="{{ old('email', $settings->email) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <p class="mt-1 text-xs text-gray-500">Shown on the contact page and footer.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Support Email</label>
                    <input type="email" name="support_email" value="{{ old('support_email', $settings->support_email) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" placeholder="{{ $settings->email ?: 'inquiries@example.com' }}">
                    <p class="mt-1 text-xs text-gray-500">Receives contact form email notifications. Falls back to Business Email if empty.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp Number</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp', $settings->whatsapp) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" placeholder="+92-3XX-XXXXXXX">
                    <p class="mt-1 text-xs text-gray-500">Include country code. Used for WhatsApp chat links and inquiry handoff.</p>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4 space-y-3">
                <p class="text-sm font-medium text-gray-900">Contact form notifications</p>

                <label class="flex items-start gap-3 text-sm text-gray-700">
                    <input type="hidden" name="contact_form_enabled" value="0">
                    <input type="checkbox" name="contact_form_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm" @checked(old('contact_form_enabled', $settings->contact_form_enabled ?? true))>
                    <span>
                        <span class="font-medium text-gray-900">Contact Form Enabled</span>
                        <span class="block text-xs text-gray-500">When disabled, customers can still view contact details but cannot submit the form.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 text-sm text-gray-700">
                    <input type="hidden" name="email_notifications_enabled" value="0">
                    <input type="checkbox" name="email_notifications_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm" @checked(old('email_notifications_enabled', $settings->email_notifications_enabled ?? true))>
                    <span>
                        <span class="font-medium text-gray-900">Email Notifications Enabled</span>
                        <span class="block text-xs text-gray-500">Send an email to Support Email when a contact inquiry is submitted.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 text-sm text-gray-700">
                    <input type="hidden" name="whatsapp_notifications_enabled" value="0">
                    <input type="checkbox" name="whatsapp_notifications_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm" @checked(old('whatsapp_notifications_enabled', $settings->whatsapp_notifications_enabled ?? true))>
                    <span>
                        <span class="font-medium text-gray-900">WhatsApp Notifications Enabled</span>
                        <span class="block text-xs text-gray-500">Offer a WhatsApp chat link on the inquiry success page after form submission.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 text-sm text-gray-700">
                    <input type="hidden" name="auto_reply_enabled" value="0">
                    <input type="checkbox" name="auto_reply_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm" @checked(old('auto_reply_enabled', $settings->auto_reply_enabled ?? false))>
                    <span>
                        <span class="font-medium text-gray-900">Auto Reply Enabled</span>
                        <span class="block text-xs text-gray-500">Send an automatic acknowledgement email to customers after they submit the contact form.</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">Commerce</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Currency</label>
                    <input type="text" name="currency" value="{{ old('currency', $settings->currency) }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Flat Shipping Rate</label>
                    <input type="number" step="0.01" name="shipping_flat_rate" value="{{ old('shipping_flat_rate', $settings->shipping_flat_rate) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <p class="mt-1 text-xs text-gray-500">Legacy field. Configure shipping in <a href="{{ route('admin.shipping.settings.edit') }}" class="text-indigo-600 hover:underline">Shipping Settings</a>.</p>
                </div>
            </div>
            <p class="text-sm text-gray-500">Tax and additional charges are managed in <a href="{{ route('admin.tax.settings.edit') }}" class="text-indigo-600 hover:underline">Tax &amp; Charges</a>.</p>
        </div>

        @can('update', $settings)
            <x-primary-button>Save Settings</x-primary-button>
        @endcan
    </form>
@endsection
