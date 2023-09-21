<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use Exception;

class ValidationException extends Exception
{
    public readonly array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;

        parent::__construct(
            'Validation failed with: '.json_encode($errors),
            422
        );
    }
}
