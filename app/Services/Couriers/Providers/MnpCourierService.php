<?php

namespace App\Services\Couriers\Providers;

use App\Services\Couriers\AbstractCourierService;

class MnpCourierService extends AbstractCourierService
{
    public function slug(): string
    {
        return 'mnp';
    }
}
