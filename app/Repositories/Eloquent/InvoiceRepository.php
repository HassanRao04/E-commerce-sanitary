<?php

namespace App\Repositories\Eloquent;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Invoice);
    }

    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Invoice::query()->with('order');

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', "%{$term}%")
                    ->orWhere('billing_name', 'like', "%{$term}%")
                    ->orWhere('billing_email', 'like', "%{$term}%")
                    ->orWhereHas('order', fn ($oq) => $oq->where('order_number', 'like', "%{$term}%"));
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        if (($filters['overdue'] ?? false) === '1') {
            $query->overdue();
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }
}
