<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\ApiErrorDtoFactory;

/**
 * One entry of the errors list a 422 carries. The gateway adds a random
 * three-character key to each, which rides along untouched.
 *
 * @property-read string|null $property
 * @property-read string|null $message
 *
 * @method static ApiErrorDtoFactory factory($count = null, $state = [])
 */
class ApiErrorDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected static function newFactory(): ApiErrorDtoFactory
    {
        return ApiErrorDtoFactory::new();
    }
}
