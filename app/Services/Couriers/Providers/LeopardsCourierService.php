<?php

namespace App\Services\Couriers\Providers;

use App\Services\Couriers\AbstractCourierService;

class LeopardsCourierService extends AbstractCourierService
{
    public function slug(): string
    {
        return 'leopards';
    }
}
