<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\CouponService;
use App\Services\OrderWorkflowService;
use App\Support\Exports\SimpleXlsxExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InfluencerPerformanceController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly OrderWorkflowService $workflow,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('coupons.view'), 403);

        $rows = $this->couponService->influencerPerformance();

        return view('admin.influencer-performance.index', compact('rows'));
    }

    public function show(Request $request, User $influencer): View
    {
        abort_unless(auth()->user()?->can('coupons.view'), 403);
        $this->assertInfluencer($influencer);

        $filters = $this->couponService->influencerOrderFilters($request);
        $summary = $this->couponService->influencerSummary($influencer, $filters);
        $wallet = $this->couponService->influencerWallet($influencer, $filters);
        $ledger = $this->couponService->influencerLedger($influencer);
        $coupons = $influencer->influencerCoupons()->latest('id')->get();
        $customers = $this->couponService->influencerCustomers($influencer, $filters);
        $orders = $this->couponService->influencerOrdersQuery($influencer, $filters)
            ->with(['trackedCoupon:id,code'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.influencer-performance.show', [
            'influencer' => $influencer,
            'filters' => $filters,
            'summary' => $summary,
            'wallet' => $wallet,
            'ledger' => $ledger,
            'coupons' => $coupons,
            'customers' => $customers,
            'orders' => $orders,
            'couponOptions' => $coupons,
            'statuses' => $this->workflow->active(),
        ]);
    }

    public function export(Request $request, User $influencer, string $format): StreamedResponse
    {
        abort_unless(auth()->user()?->can('coupons.view'), 403);
        $this->assertInfluencer($influencer);
        abort_unless(in_array($format, ['csv', 'excel'], true), 404);

        $filters = $this->couponService->influencerOrderFilters($request);
        $orders = $this->couponService->influencerOrdersQuery($influencer, $filters)
            ->with(['trackedCoupon:id,code'])
            ->latest('created_at')
            ->get();

        $headers = $this->couponService->influencerExportHeaders();
        $rows = $this->couponService->influencerExportRows($orders);
        $slug = str($influencer->name)->slug()->value() ?: 'influencer';

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $rows): void {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }, "influencer-{$slug}-orders.csv", [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $binary = (new SimpleXlsxExporter)->stream($headers, $rows, 'Influencer Orders');

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, "influencer-{$slug}-orders.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function markPaid(User $influencer, Order $order): RedirectResponse
    {
        abort_unless(auth()->user()?->can('coupons.manage'), 403);
        $this->assertInfluencer($influencer);
        abort_unless(
            (int) $order->influencer_id === (int) $influencer->id
            && $order->influencer_commission_paid_at === null,
            404
        );

        $amount = (float) $order->influencer_commission_amount;
        $this->couponService->markCommissionPaid($order);

        return redirect()
            ->back()
            ->with('success', 'Commission marked as paid ('.config('shop.currency_symbol').number_format($amount, 2).').');
    }

    public function markSelectedPaid(Request $request, User $influencer): RedirectResponse
    {
        abort_unless(auth()->user()?->can('coupons.manage'), 403);
        $this->assertInfluencer($influencer);

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct'],
        ]);

        $result = $this->couponService->markCommissionsPaid(
            $influencer,
            $validated['order_ids'],
        );

        $message = "{$result['processed']} commission(s) marked as paid.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} skipped (already paid or invalid).";
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    public function payCommission(Request $request, User $influencer, Order $order): RedirectResponse
    {
        abort_unless(auth()->user()?->can('coupons.manage'), 403);
        $this->assertInfluencer($influencer);
        abort_unless(
            (int) $order->influencer_id === (int) $influencer->id
            && $order->influencer_commission_paid_at === null,
            404
        );

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'transaction_id' => ['nullable', 'string', 'max:191'],
        ]);

        $this->couponService->payOrderCommission(
            $order,
            (float) $validated['amount'],
            $validated['admin_notes'] ?? null,
            auth()->user(),
            $validated['transaction_id'] ?? null,
        );

        return redirect()
            ->route('admin.influencer-performance.show', $influencer)
            ->with('success', 'Commission paid: ledger debit recorded and order marked paid ('.config('shop.currency_symbol').number_format((float) $validated['amount'], 2).').');
    }

    public function recordPayout(Request $request, User $influencer): RedirectResponse
    {
        abort_unless(auth()->user()?->can('coupons.manage'), 403);
        $this->assertInfluencer($influencer);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'transaction_id' => ['nullable', 'string', 'max:191'],
        ]);

        $this->couponService->recordCommissionDebit(
            $influencer,
            (float) $validated['amount'],
            $validated['admin_notes'] ?? 'Manual payout',
            auth()->user(),
            null,
            $validated['transaction_id'] ?? null,
        );

        return redirect()
            ->back()
            ->with('success', 'Manual payout recorded ('.config('shop.currency_symbol').number_format((float) $validated['amount'], 2).').');
    }

    private function assertInfluencer(User $influencer): void
    {
        $isInfluencer = $influencer->hasRole('influencer')
            || $influencer->influencerCoupons()->exists()
            || $influencer->influencerOrders()->exists();

        abort_unless($isInfluencer, 404);
    }
}
