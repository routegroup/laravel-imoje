<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\ServicePaymentMethodDtoFactory;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

/**
 * One channel as a service reports it. isOnline is the flag chapter 05 points
 * at for avoiding a 406: check it before offering the channel.
 *
 * @property-read PaymentMethod|null $paymentMethod
 * @property-read PaymentMethodCode|null $paymentMethodCode
 * @property-read bool|null $isActive
 * @property-read bool|null $isOnline
 * @property-read string|null $description
 * @property-read Currency|null $currency
 * @property-read TransactionLimitsDto|null $transactionLimits
 * @property-read array|null $paymentMethodImage
 * @property-read array|null $paymentMethodCodeImage
 *
 * @method static ServicePaymentMethodDtoFactory factory($count = null, $state = [])
 */
class ServicePaymentMethodDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'paymentMethod' => PaymentMethod::class,
        'paymentMethodCode' => PaymentMethodCode::class,
        'isActive' => 'bool',
        'isOnline' => 'bool',
        'currency' => Currency::class,
        'transactionLimits' => TransactionLimitsDto::class,
    ];

    protected static function newFactory(): ServicePaymentMethodDtoFactory
    {
        return ServicePaymentMethodDtoFactory::new();
    }
}
