<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;
use Routegroup\Imoje\Payment\Factories\Responses\PaymentResponseDtoFactory;

/**
 * @property-read PaymentDto $payment
 */
class PaymentResponseDto extends ResponseDto
{
    use HasFactory;

    protected array $casts = [
        'payment' => PaymentDto::class,
    ];

    protected static function newFactory(): PaymentResponseDtoFactory
    {
        return PaymentResponseDtoFactory::new();
    }
}
