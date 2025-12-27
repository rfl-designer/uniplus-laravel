<?php

declare(strict_types=1);

namespace Uniplus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Uniplus\Auth\Token;

class TokenRefreshed
{
    use Dispatchable;

    public function __construct(
        public readonly string $connection,
        public readonly Token $token,
    ) {}
}
