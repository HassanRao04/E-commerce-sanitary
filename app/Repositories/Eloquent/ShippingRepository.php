<?php

namespace App\Repositories\Eloquent;

use App\Models\Shipping;
use App\Repositories\Contracts\ShippingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShippingRepository extends BaseRepository implements ShippingRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Shipping);
    }

    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Shipping::query()->with('order');

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('tracking_number', 'like', "%{$term}%")
                    ->orWhere('courier_name', 'like', "%{$term}%")
                    ->orWhereHas('order', fn ($oq) => $oq->where('order_number', 'like', "%{$term}%"));
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }
}
