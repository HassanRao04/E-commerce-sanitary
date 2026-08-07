<?php

namespace App\DataTransferObjects;

readonly class CourierTrackingResultDTO
{
    /** @param  list<array{status: string, location: ?string, description: ?string, event_at: string}>  $events */
    public function __construct(
        public bool $success,
        public ?string $message = null,
        public array $events = [],
        public array $metadata = [],
    ) {}
}
