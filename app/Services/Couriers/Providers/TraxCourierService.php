<?php

namespace App\Services\Couriers\Providers;

use App\Services\Couriers\AbstractCourierService;

class TraxCourierService extends AbstractCourierService
{
    public function slug(): string
    {
        return 'trax';
    }
}
