<?php

declare(strict_types=1);

namespace Uniplus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Uniplus\Http\Response;

class RequestSent
{
    use Dispatchable;

    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly Response $response,
        public readonly float $durationMs,
    ) {}
}
