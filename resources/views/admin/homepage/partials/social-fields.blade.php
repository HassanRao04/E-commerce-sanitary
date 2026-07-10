@php
    use App\Support\SocialLinks;

    $social = $storefrontHeader['social'] ?? [];
    $socialLinks = $settings->social_links ?? [];
@endphp

<div id="social-media" class="mt-8 rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-indigo-50/60">
        <h3 class="font-semibold text-gray-900">Social media icons</h3>
        <p class="text-sm text-gray-600">Add your profile links here. Icons appear in the <strong>top bar</strong> and <strong>footer</strong> on the storefront. Use the button below — not “Save homepage sections”.</p>
    </div>

    @can('homepage.manage')
        <form method="POST" action="{{ route('admin.homepage.social.update') }}" class="p-6 space-y-6">
            @csrf
            @method('PATCH')

            <div class="flex flex-wrap gap-5 text-sm">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="social[show_in_top_bar]" value="0">
                    <input type="checkbox" name="social[show_in_top_bar]" value="1" @checked(old('social.show_in_top_bar', $social['show_in_top_bar'] ?? true))>
                    Show in top bar
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="social[show_in_footer]" value="0">
                    <input type="checkbox" name="social[show_in_footer]" value="1" @checked(old('social.show_in_footer', $social['show_in_footer'] ?? true))>
                    Show in footer
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach (SocialLinks::platforms() as $key => $profile)
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $profile['label'] }}</label>
                        <input
                            type="text"
                            name="social_links[{{ $key }}]"
                            value="{{ old("social_links.{$key}", $socialLinks[$key] ?? '') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"
                            placeholder="https://{{ $key === 'twitter' ? 'x.com/your-page' : $key.'.com/your-page' }}"
                            inputmode="url"
                            autocomplete="url"
                        >
                        <p class="mt-1 text-xs text-gray-500">Leave blank to hide the {{ $profile['label'] }} icon.</p>
                    </div>
                @endforeach
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">WhatsApp number</label>
                <input
                    type="text"
                    name="whatsapp"
                    value="{{ old('whatsapp', $settings->whatsapp) }}"
                    class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm text-sm"
                    placeholder="+923001234567"
                >
                <p class="mt-1 text-xs text-gray-500">Shows a WhatsApp icon that opens chat. Include country code.</p>
            </div>

            <x-primary-button>Save social media links</x-primary-button>
        </form>
    @else
        <div class="p-6 text-sm text-gray-500">You have view-only access to social settings.</div>
    @endcan
</div>
