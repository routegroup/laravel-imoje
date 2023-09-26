<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentProfileDto;
use Routegroup\Imoje\Payment\Factories\Casts\PaymentProfileDtoFactory;

/**
 * @property-read PaymentProfileDto $transaction
 */
class ProfileResponseDto extends ResponseDto
{
    use HasFactory;

    protected array $casts = [
        'paymentProfile' => PaymentProfileDto::class,
    ];

    protected static function newFactory(): PaymentProfileDtoFactory
    {
        return PaymentProfileDtoFactory::new();
    }
}
