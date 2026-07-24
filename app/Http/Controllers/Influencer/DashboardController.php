<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
    ) {}

    public function __invoke(): View
    {
        $user = auth()->user();
        abort_unless($user?->isInfluencer(), 403);

        $summary = $this->couponService->influencerSummary($user);
        $wallet = $this->couponService->influencerWallet($user);
        $couponUsage = $this->couponService->influencerPerformance()
            ->where('influencer_id', $user->id)
            ->values();
        $latestOrders = $this->couponService->influencerOrdersQuery($user)
            ->with(['trackedCoupon:id,code'])
            ->latest('created_at')
            ->limit(10)
            ->get();
        $latestPayouts = $this->couponService->influencerLatestPayouts($user, 10);

        return view('influencer.dashboard', [
            'summary' => $summary,
            'wallet' => $wallet,
            'couponUsage' => $couponUsage,
            'latestOrders' => $latestOrders,
            'latestPayouts' => $latestPayouts,
        ]);
    }
}
