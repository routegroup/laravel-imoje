<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use Exception;

class NotImplementedException extends Exception
{
    /**
     * {@inheritDoc}
     */
    protected $message = 'Given response has been not recognized';

    /**
     * {@inheritDoc}
     */
    protected $code = '501';
}
