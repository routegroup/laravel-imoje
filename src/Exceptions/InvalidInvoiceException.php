<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use InvalidArgumentException;

class InvalidInvoiceException extends InvalidArgumentException
{
    public readonly string $field;

    public function __construct(string $field, string $reason)
    {
        $this->field = $field;

        parent::__construct("invoice.$field: $reason");
    }

    public function context(): array
    {
        return ['field' => $this->field];
    }
}
