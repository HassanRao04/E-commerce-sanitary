<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourierProviderRequest;
use App\Http\Requests\Admin\UpdateCourierProviderRequest;
use App\Models\CourierProvider;
use App\Services\Admin\CourierProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CourierProviderController extends Controller
{
    public function __construct(private readonly CourierProviderService $courierProviders) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CourierProvider::class);

        return view('admin.courier-providers.index', [
            'providers' => $this->courierProviders->paginatedList($request->string('q')->toString() ?: null),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', CourierProvider::class);

        return view('admin.courier-providers.form', [
            'provider' => new CourierProvider([
                'is_active' => false,
                'is_sandbox' => true,
            ]),
        ]);
    }

    public function store(StoreCourierProviderRequest $request): RedirectResponse
    {
        $this->courierProviders->create(
            $request->validated(),
            $request->file('logo'),
        );

        return redirect()->route('admin.courier-providers.index')
            ->with('success', 'Courier provider created successfully.');
    }

    public function edit(CourierProvider $courierProvider): View
    {
        $this->authorize('update', $courierProvider);

        return view('admin.courier-providers.form', [
            'provider' => $courierProvider,
        ]);
    }

    public function update(UpdateCourierProviderRequest $request, CourierProvider $courierProvider): RedirectResponse
    {
        $this->courierProviders->update(
            $courierProvider,
            $request->validated(),
            $request->file('logo'),
        );

        return redirect()->route('admin.courier-providers.index')
            ->with('success', 'Courier provider updated successfully.');
    }

    public function destroy(CourierProvider $courierProvider): RedirectResponse
    {
        $this->authorize('delete', $courierProvider);

        try {
            $this->courierProviders->delete($courierProvider);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.courier-providers.index')
            ->with('success', 'Courier provider deleted successfully.');
    }
}
