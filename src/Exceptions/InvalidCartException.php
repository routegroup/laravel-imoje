<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use InvalidArgumentException;

class InvalidCartException extends InvalidArgumentException
{
    public readonly string $field;

    public function __construct(string $field, string $reason)
    {
        $this->field = $field;

        parent::__construct("cart.$field: $reason");
    }

    public function context(): array
    {
        return ['field' => $this->field];
    }
}
