<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;
use Routegroup\Imoje\Payment\Factories\Api\PaymentDtoFactory;

/**
 * @property-read PaymentDto $payment
 */
class PaymentResponseDto extends ResponseDto
{
    use HasFactory;

    protected array $casts = [
        'payment' => PaymentDto::class,
    ];

    protected static function newFactory(): PaymentDtoFactory
    {
        return PaymentDtoFactory::new();
    }
}
