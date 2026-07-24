<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Coupon;
use App\Services\ActivityLogService;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly UserService $users,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Coupon::class);

        $coupons = Coupon::query()
            ->with('influencer:id,name,email')
            ->when($request->filled('q'), fn ($q) => $q->where('code', 'like', '%'.$request->input('q').'%'))
            ->when($request->boolean('active_only'), fn ($q) => $q->valid())
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        $this->authorize('create', Coupon::class);

        return view('admin.coupons.form', [
            'coupon' => new Coupon(['is_active' => true, 'commission_enabled' => false]),
            'influencers' => $this->users->listInfluencers(),
        ]);
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $coupon = Coupon::create($this->payload($request->validated()));

        $this->activityLog->log('coupon.created', $coupon, [], [
            'code' => $coupon->code,
            'type' => $coupon->type->value,
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorize('update', $coupon);

        return view('admin.coupons.form', [
            'coupon' => $coupon,
            'influencers' => $this->users->listInfluencers($coupon->influencer_id),
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->payload($request->validated()));

        $this->activityLog->log('coupon.updated', $coupon, [], [
            'code' => $coupon->code,
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('delete', $coupon);

        $code = $coupon->code;
        $coupon->delete();

        $this->activityLog->log('coupon.deleted', null, [], ['code' => $code]);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully.');
    }

    /** @param array<string, mixed> $data */
    private function payload(array $data): array
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['commission_enabled'] = (bool) ($data['commission_enabled'] ?? false);

        foreach ([
            'min_order_amount',
            'max_uses',
            'starts_at',
            'expires_at',
            'influencer_id',
            'commission_type',
            'commission_value',
        ] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
