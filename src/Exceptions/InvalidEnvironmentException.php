<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use InvalidArgumentException;
use Routegroup\Imoje\Payment\Types\Environment;

class InvalidEnvironmentException extends InvalidArgumentException
{
    public readonly string $given;

    public function __construct(string $given)
    {
        $this->given = $given;

        parent::__construct(sprintf(
            'Invalid [services.imoje.env] value [%s]. Allowed values: %s.',
            $given,
            implode(', ', array_column(Environment::cases(), 'value')),
        ));
    }

    public function context(): array
    {
        return [
            'given' => $this->given,
            'allowed' => array_column(Environment::cases(), 'value'),
        ];
    }
}
