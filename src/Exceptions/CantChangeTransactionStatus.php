<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use InvalidArgumentException;

class CantChangeTransactionStatus extends InvalidArgumentException
{
    /**
     * {@inheritdoc}
     */
    protected $message = 'Cant change transaction status.';
}
