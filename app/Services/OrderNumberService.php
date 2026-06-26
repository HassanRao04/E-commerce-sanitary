<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderNumberSequence;
use Illuminate\Support\Facades\DB;

class OrderNumberService
{
    public function generate(): string
    {
        return DB::transaction(function (): string {
            $year = (int) now()->year;

            $sequence = OrderNumberSequence::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['year' => $year],
                    ['last_number' => 0],
                );

            do {
                $sequence->increment('last_number');
                $sequence->refresh();
                $number = sprintf('ORD-%d-%06d', $year, $sequence->last_number);
            } while (Order::query()->where('order_number', $number)->exists());

            return $number;
        });
    }
}
