<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\Admin\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        return view('admin.customers.index', [
            'customers' => $this->customerService->paginatedList($request->only('q', 'customer_type')),
        ]);
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer = $this->customerService->findWithRelations($customer->id);

        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        $customer->load('user');

        return view('admin.customers.form', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customerService->update($customer, $request->validated());

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer updated.');
    }
}
