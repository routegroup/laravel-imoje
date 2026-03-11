<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\Factories\Responses\PaymentMethodsResponseDtoFactory;

/**
 * @method static PaymentMethodsResponseDtoFactory factory($count = null, $state = [])
 */
class PaymentMethodsResponseDto extends ResponseDto
{
    use HasFactory;

    protected static function newFactory(): PaymentMethodsResponseDtoFactory
    {
        return PaymentMethodsResponseDtoFactory::new();
    }
}
