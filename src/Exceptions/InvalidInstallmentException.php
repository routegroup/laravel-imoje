<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use InvalidArgumentException;

class InvalidInstallmentException extends InvalidArgumentException
{
    public readonly mixed $period;

    public function __construct(mixed $period, string $reason)
    {
        $this->period = $period;

        parent::__construct("installment.period: $reason");
    }

    public function context(): array
    {
        return ['period' => $this->period];
    }
}
