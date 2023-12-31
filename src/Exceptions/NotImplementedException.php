<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use Exception;

class NotImplementedException extends Exception
{
    public readonly array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
        parent::__construct('Given response has been not recognized', 501);
    }

    public function context(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}
