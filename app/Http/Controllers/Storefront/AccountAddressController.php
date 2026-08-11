<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\AddressType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddressRequest;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountAddressController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $addresses = $user->addresses()->latest()->get();

        return view('storefront.account.addresses.index', compact('addresses'));
    }

    public function create(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('storefront.account.addresses.form', [
            'address' => new Address(['country' => 'Pakistan', 'type' => AddressType::Shipping]),
        ]);
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $this->createAddress($request->validated());

        return redirect()
            ->route('shop.account.addresses.index')
            ->with('success', 'Address saved successfully.');
    }

    public function edit(Address $address): View|RedirectResponse
    {
        $this->authorizeAddress($address);

        return view('storefront.account.addresses.form', compact('address'));
    }

    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        $this->authorizeAddress($address);

        $address->update($request->validated());

        if ($request->boolean('is_default')) {
            $this->markDefault($address);
        }

        return redirect()
            ->route('shop.account.addresses.index')
            ->with('success', 'Address updated successfully.');
    }

    public function destroy(Address $address): RedirectResponse
    {
        $this->authorizeAddress($address);

        $address->delete();

        return redirect()
            ->route('shop.account.addresses.index')
            ->with('success', 'Address removed.');
    }

    /** @param array<string, mixed> $data */
    private function createAddress(array $data): Address
    {
        $address = auth()->user()->addresses()->create($data);

        if ($data['is_default'] ?? false) {
            $this->markDefault($address);
        }

        return $address;
    }

    private function markDefault(Address $address): void
    {
        auth()->user()->addresses()
            ->where('id', '!=', $address->id)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);
    }

    private function authorizeAddress(Address $address): void
    {
        abort_unless($address->user_id === auth()->id(), 403);
    }
}
