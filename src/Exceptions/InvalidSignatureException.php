<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use Exception;

class InvalidSignatureException extends Exception
{
    protected $message = 'Provided signature is invalid.';
}
