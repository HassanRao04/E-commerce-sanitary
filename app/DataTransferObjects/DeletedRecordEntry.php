<?php

namespace App\DataTransferObjects;

use App\Models\User;
use Illuminate\Support\Carbon;

readonly class DeletedRecordEntry
{
    public function __construct(
        public string $type,
        public string $typeLabel,
        public int $id,
        public string $identifier,
        public ?string $subtitle,
        public ?Carbon $deletedAt,
        public ?User $deletedBy,
        public string $status,
    ) {}
}
