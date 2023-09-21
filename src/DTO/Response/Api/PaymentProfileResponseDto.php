<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Response\Api;

use Routegroup\Imoje\Payment\DTO\BaseDto;

/**
 * @property-read string $id
 * @property-read int $year
 * @property-read int $month
 * @property-read string $profile
 * @property-read bool $isActive
 * @property-read string $lastName
 * @property-read string $firstName
 * @property-read string $merchantMid
 * @property-read string $maskedNumber
 * @property-read string $organization
 * @property-read string $merchantCustomerId
 */
class PaymentProfileResponseDto extends BaseDto
{
    protected array $casts = [
        'year' => 'int',
        'month' => 'int',
        'isActive' => 'bool',
    ];
}
