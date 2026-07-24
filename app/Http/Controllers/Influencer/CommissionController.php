<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use App\Support\Exports\SimpleXlsxExporter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommissionController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
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

        return view('influencer.commissions', [
            'orders' => $orders,
            'filters' => $filters,
            'wallet' => $this->couponService->influencerWallet($user, $filters),
        ]);
    }

    public function wallet(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user?->isInfluencer(), 403);

        $filters = $this->couponService->influencerOrderFilters($request);
        $wallet = $this->couponService->influencerWallet($user, $filters);
        $ledger = $this->couponService->influencerLedger($user);
        $payoutHistory = $this->couponService->influencerPayoutHistory($user);

        return view('influencer.wallet', [
            'wallet' => $wallet,
            'ledger' => $ledger,
            'payoutHistory' => $payoutHistory,
            'filters' => $filters,
        ]);
    }

    public function export(Request $request, string $format): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user?->isInfluencer(), 403);
        abort_unless(in_array($format, ['csv', 'excel'], true), 404);

        $filters = $this->couponService->influencerOrderFilters($request);
        $orders = $this->couponService->influencerOrdersQuery($user, $filters)
            ->with(['trackedCoupon:id,code'])
            ->latest('created_at')
            ->get();

        $headers = $this->couponService->influencerCommissionExportHeaders();
        $rows = $this->couponService->influencerCommissionExportRows($orders);
        $slug = str($user->name)->slug()->value() ?: 'influencer';

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $rows): void {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }, "influencer-{$slug}-commissions.csv", [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $binary = (new SimpleXlsxExporter)->stream($headers, $rows, 'Commission History');

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, "influencer-{$slug}-commissions.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
