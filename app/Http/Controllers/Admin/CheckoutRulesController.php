<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCheckoutRulesSettingsRequest;
use App\Models\CheckoutRulesSetting;
use App\Services\ActivityLogService;
use App\Services\CheckoutRulesEngine;
use App\Services\CheckoutRulesSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutRulesController extends Controller
{
    public function __construct(
        private readonly CheckoutRulesSettingsService $checkoutRulesSettings,
        private readonly CheckoutRulesEngine $rulesEngine,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function edit(): View
    {
        $this->authorize('viewAny', CheckoutRulesSetting::class);

        return view('admin.checkout.rules', [
            'settings' => CheckoutRulesSetting::current(),
            'rules' => $this->rulesEngine->rulesSnapshot(),
        ]);
    }

    public function update(UpdateCheckoutRulesSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->checkoutRulesSettings->sync($validated);

        $this->activityLog->log('checkout_rules.settings.updated', CheckoutRulesSetting::current(), [], [
            'minimum_order_enabled' => $validated['minimum_order_enabled'],
            'minimum_order_amount' => $validated['minimum_order_amount'],
            'coupons_enabled' => $validated['coupons_enabled'],
        ]);

        return back()->with('success', 'Checkout rules saved successfully.');
    }
}
