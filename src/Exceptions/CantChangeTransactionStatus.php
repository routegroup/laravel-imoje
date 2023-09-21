<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use InvalidArgumentException;

class CantChangeTransactionStatus extends InvalidArgumentException
{
    protected $message = 'Cant change transaction status.';
}
