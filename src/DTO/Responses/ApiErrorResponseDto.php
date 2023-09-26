<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Routegroup\Imoje\Payment\DTO\BaseDto;

/**
 * @property-read string $code
 * @property-read string $message
 * @property-read mixed $instance
 * @property-read array $errors
 */
class ApiErrorResponseDto extends BaseDto
{
    protected array $casts = [
        'code' => 'string',
        'message' => 'string',
        'errors' => 'array',
    ];
}
