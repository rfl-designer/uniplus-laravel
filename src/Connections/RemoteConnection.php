<?php

declare(strict_types=1);

namespace Uniplus\Connections;

use Uniplus\Exceptions\ConnectionException;

class RemoteConnection extends Connection
{
    public function getBaseUrl(): string
    {
        if ($this->baseUrl === null || $this->baseUrl === '') {
            throw new ConnectionException(
                "Missing 'server_url' in Uniplus connection config for account '{$this->account}'. The consumer must provide the server URL per connection.",
            );
        }

        return $this->baseUrl;
    }
}
