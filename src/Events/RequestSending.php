<?php

declare(strict_types=1);

namespace Uniplus\Events;

use Illuminate\Foundation\Events\Dispatchable;

class RequestSending
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly array $payload = [],
    ) {}
}
