<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use InvalidArgumentException;

class InvalidDtoNameException extends InvalidArgumentException
{
    protected $message = 'Given name is not a subclass of a DTO.';
}
