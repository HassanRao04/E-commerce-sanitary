<?php

namespace App\Services\Couriers\Providers;

use App\Services\Couriers\AbstractCourierService;

class CallCourierService extends AbstractCourierService
{
    public function slug(): string
    {
        return 'call_courier';
    }
}
