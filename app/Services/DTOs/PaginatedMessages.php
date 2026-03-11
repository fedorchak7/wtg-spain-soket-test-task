<?php

namespace App\Services\DTOs;

use Illuminate\Support\Collection;

final readonly class PaginatedMessages
{
    public function __construct(
        public Collection $messages,
        public bool $hasMore,
    ) {}
}
