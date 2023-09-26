<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\PaymentProfileDtoFactory;

/**
 * @property-read string|null $id
 * @property-read string $merchantMid
 * @property-read string|null $merchantCustomerId
 * @property-read string|null $firstName
 * @property-read string|null $lastName
 * @property-read string|null $maskedNumber
 * @property-read int|null $month
 * @property-read int|null $year
 * @property-read string|null $organization
 * @property-read bool|null $isActive
 * @property-read string|null $profile
 */
class PaymentProfileDto extends BaseDto
{
    use HasFactory;

    protected $attributes = [
        'id' => null,
        'merchantMid' => null,
        'merchantCustomerId' => null,
        'firstName' => null,
        'lastName' => null,
        'maskedNumber' => null,
        'month' => null,
        'year' => null,
        'organization' => null,
        'isActive' => null,
        'profile' => null,
    ];

    protected array $casts = [
        'month' => 'int',
        'year' => 'int',
        'isActive' => 'bool',
    ];

    protected static function newFactory(): PaymentProfileDtoFactory
    {
        return PaymentProfileDtoFactory::new();
    }
}
