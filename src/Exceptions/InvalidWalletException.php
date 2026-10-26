<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use InvalidArgumentException;

/** Covers the whole wallet flow, browser data included, so the field carries its own full path. */
class InvalidWalletException extends InvalidArgumentException
{
    public readonly string $field;

    public function __construct(string $field, string $reason)
    {
        $this->field = $field;

        parent::__construct("$field: $reason");
    }

    public function context(): array
    {
        return ['field' => $this->field];
    }
}
