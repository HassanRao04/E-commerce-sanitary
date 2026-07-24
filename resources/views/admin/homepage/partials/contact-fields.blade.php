@php
    $contact = $storefrontContact ?? \App\Support\StorefrontContact::resolved();
    $settings = $settings ?? \App\Models\SiteSetting::current();
@endphp

<div id="contact-content" class="mt-8 rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900">Contact information</h3>
        <p class="text-sm text-gray-500">Contact page copy and business details shown on the storefront contact page and footer. Notification toggles and support email are managed in <a href="{{ route('admin.settings.index') }}" class="text-indigo-600 hover:underline">Site Settings</a>.</p>
    </div>

    @can('homepage.manage')
        <form method="POST" action="{{ route('admin.homepage.contact.update') }}" class="p-6 space-y-5">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Page title</label>
                    <input type="text" name="contact[page_title]" value="{{ old('contact.page_title', $contact['page_title'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Business hours</label>
                    <input type="text" name="contact[business_hours]" value="{{ old('contact.business_hours', $contact['business_hours'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Intro text</label>
                <textarea name="contact[intro]" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('contact.intro', $contact['intro'] ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="contact[email]" value="{{ old('contact.email', $settings->email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="contact[contact_phone]" value="{{ old('contact.contact_phone', $settings->contact_phone) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                    <input type="text" name="contact[whatsapp]" value="{{ old('contact.whatsapp', $settings->whatsapp) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" name="contact[address]" value="{{ old('contact.address', $settings->address) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                <input type="hidden" name="contact[show_order_tracking]" value="0">
                <input type="checkbox" name="contact[show_order_tracking]" value="1" @checked(old('contact.show_order_tracking', $contact['show_order_tracking'] ?? true))>
                Show order tracking link on contact page
            </label>

            <div>
                <label class="block text-sm font-medium text-gray-700">Order tracking label</label>
                <input type="text" name="contact[order_tracking_label]" value="{{ old('contact.order_tracking_label', $contact['order_tracking_label'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <x-primary-button>Save contact information</x-primary-button>
        </form>
    @else
        <div class="p-6 text-sm text-gray-500">You have view-only access to contact settings.</div>
    @endcan
</div>
