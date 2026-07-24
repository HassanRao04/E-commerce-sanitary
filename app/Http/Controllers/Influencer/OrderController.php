<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly OrderWorkflowService $workflow,
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user?->isInfluencer(), 403);

        $filters = $this->couponService->influencerOrderFilters($request);
        $orders = $this->couponService->influencerOrdersQuery($user, $filters)
            ->with(['trackedCoupon:id,code'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('influencer.orders', [
            'orders' => $orders,
            'filters' => $filters,
            'couponOptions' => $user->influencerCoupons()->latest('id')->get(['id', 'code']),
            'statuses' => $this->workflow->active(),
        ]);
    }
}
