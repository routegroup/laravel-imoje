<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\PaymentMethodChannelDtoFactory;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

/**
 * @property-read PaymentMethod|null $paymentMethod
 * @property-read PaymentMethodCode|null $paymentMethodCode
 * @property-read string|null $label
 * @property-read int|null $priority
 * @property-read bool|null $isOnline
 * @property-read array|null $image
 *
 * @method static PaymentMethodChannelDtoFactory factory($count = null, $state = [])
 */
class PaymentMethodChannelDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'paymentMethod' => PaymentMethod::class,
        'paymentMethodCode' => PaymentMethodCode::class,
        'priority' => 'int',
        'isOnline' => 'bool',
    ];

    protected static function newFactory(): PaymentMethodChannelDtoFactory
    {
        return PaymentMethodChannelDtoFactory::new();
    }
}
