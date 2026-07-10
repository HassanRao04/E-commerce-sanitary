<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderStatusDefinitionRequest;
use App\Http\Requests\Admin\UpdateOrderStatusDefinitionRequest;
use App\Models\OrderStatus;
use App\Services\Admin\OrderWorkflowAdminService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderWorkflowController extends Controller
{
    public function __construct(
        private readonly OrderWorkflowAdminService $adminService,
        private readonly OrderWorkflowService $workflow,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', OrderStatus::class);

        return view('admin.orders.workflow.index', [
            'statuses' => $this->adminService->list(),
            'badgeColors' => $this->workflow->badgeColorOptions(),
        ]);
    }

    public function store(StoreOrderStatusDefinitionRequest $request): RedirectResponse
    {
        $this->authorize('create', OrderStatus::class);

        $this->adminService->create($request->validated());

        return back()->with('success', 'Custom order status created.');
    }

    public function update(UpdateOrderStatusDefinitionRequest $request, OrderStatus $orderStatus): RedirectResponse
    {
        $this->authorize('update', $orderStatus);

        $this->adminService->update($orderStatus, $request->validated());

        return back()->with('success', 'Order status updated.');
    }

    public function destroy(OrderStatus $orderStatus): RedirectResponse
    {
        $this->authorize('delete', $orderStatus);

        $this->adminService->delete($orderStatus);

        return back()->with('success', 'Order status deleted.');
    }
}
