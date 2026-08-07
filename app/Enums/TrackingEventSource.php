<?php

namespace App\Enums;

enum TrackingEventSource: string
{
    case Manual = 'manual';
    case Api = 'api';
    case Webhook = 'webhook';
}
