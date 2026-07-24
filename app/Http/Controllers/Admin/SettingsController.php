<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', SiteSetting::class);

        return view('admin.settings.index', [
            'settings' => SiteSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', SiteSetting::current());

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'currency' => ['required', 'string', 'max:10'],
            'shipping_flat_rate' => ['nullable', 'numeric', 'min:0'],
            'contact_form_enabled' => ['nullable', 'boolean'],
            'email_notifications_enabled' => ['nullable', 'boolean'],
            'whatsapp_notifications_enabled' => ['nullable', 'boolean'],
            'auto_reply_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['contact_form_enabled'] = $request->boolean('contact_form_enabled');
        $validated['email_notifications_enabled'] = $request->boolean('email_notifications_enabled');
        $validated['whatsapp_notifications_enabled'] = $request->boolean('whatsapp_notifications_enabled');
        $validated['auto_reply_enabled'] = $request->boolean('auto_reply_enabled');

        SiteSetting::current()->update($validated);

        return back()->with('success', 'Settings saved.');
    }
}
