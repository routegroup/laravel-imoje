<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use InvalidArgumentException;

class InvalidMultipayoutException extends InvalidArgumentException
{
    public readonly int $index;

    public readonly string $field;

    public function __construct(int $index, string $field, string $reason)
    {
        $this->index = $index;
        $this->field = $field;

        parent::__construct("multipayout[$index][$field]: $reason");
    }

    public function context(): array
    {
        return [
            'index' => $this->index,
            'field' => $this->field,
        ];
    }
}
