<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTaxChargeSettingsRequest;
use App\Models\TaxChargeSetting;
use App\Services\ActivityLogService;
use App\Services\TaxChargeSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxChargeSettingsController extends Controller
{
    public function __construct(
        private readonly TaxChargeSettingsService $taxChargeSettings,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function edit(): View
    {
        $this->authorize('viewAny', TaxChargeSetting::class);

        return view('admin.tax.settings', [
            'settings' => TaxChargeSetting::current(),
        ]);
    }

    public function update(UpdateTaxChargeSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->taxChargeSettings->sync([
            'vat_enabled' => $validated['vat_enabled'] ?? false,
            'vat_rate' => $validated['vat_rate'] ?? 0,
            'gst_enabled' => $validated['gst_enabled'] ?? false,
            'gst_rate' => $validated['gst_rate'] ?? 0,
            'sales_tax_enabled' => $validated['sales_tax_enabled'] ?? false,
            'sales_tax_rate' => $validated['sales_tax_rate'] ?? 0,
            'default_tax_type' => $validated['default_tax_type'],
            'service_charge_enabled' => $validated['service_charge_enabled'] ?? false,
            'service_charge_type' => $validated['service_charge_type'] ?? 'percent',
            'service_charge_value' => $validated['service_charge_value'] ?? 0,
            'handling_charge_enabled' => $validated['handling_charge_enabled'] ?? false,
            'handling_charge_type' => $validated['handling_charge_type'] ?? 'fixed',
            'handling_charge_value' => $validated['handling_charge_value'] ?? 0,
        ]);

        $this->activityLog->log('tax.settings.updated', TaxChargeSetting::current(), [], [
            'default_tax_type' => $validated['default_tax_type'],
        ]);

        return back()->with('success', 'Tax and charge settings saved successfully.');
    }
}
