<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use Exception;

class NotImplementedException extends Exception
{
    protected $message = 'Given response has been not recognized';

    protected $code = '501';
}
