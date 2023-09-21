<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Response;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Response\Api\PaymentProfileResponseDto;

/**
 * @property-read PaymentProfileResponseDto $transaction
 */
class ProfileResponseDto extends BaseDto
{
    protected array $casts = [
        'paymentProfile' => PaymentProfileResponseDto::class,
    ];
}
