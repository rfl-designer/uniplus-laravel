<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Exceptions\UniplusException;

/**
 * Base class for read-only resources (GET only).
 */
abstract class ReadOnlyResource extends Resource
{
    /**
     * Create is not supported for read-only resources.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws UniplusException
     */
    public function create(array $data): array
    {
        throw new UniplusException('Create operation is not supported for this resource.');
    }

    /**
     * Update is not supported for read-only resources.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws UniplusException
     */
    public function update(array $data): array
    {
        throw new UniplusException('Update operation is not supported for this resource.');
    }

    /**
     * Delete is not supported for read-only resources.
     *
     * @throws UniplusException
     */
    public function delete(string $code): bool
    {
        throw new UniplusException('Delete operation is not supported for this resource.');
    }
}
