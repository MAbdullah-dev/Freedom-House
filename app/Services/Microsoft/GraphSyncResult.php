<?php

namespace App\Services\Microsoft;

class GraphSyncResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $code,
    ) {}
}
