<?php

declare(strict_types=1);

namespace Uniplus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Throwable;
use Uniplus\Http\Response;

class RequestFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly Throwable $exception,
        public readonly ?Response $response = null,
    ) {}
}
