<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\Factories\Responses\ServicesResponseDtoFactory;

/**
 * @method static ServicesResponseDtoFactory factory($count = null, $state = [])
 */
class ServicesResponseDto extends ResponseDto
{
    use HasFactory;

    protected static function newFactory(): ServicesResponseDtoFactory
    {
        return ServicesResponseDtoFactory::new();
    }
}
