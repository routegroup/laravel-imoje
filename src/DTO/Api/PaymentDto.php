<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\CartDto;
use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoiceDto;
use Routegroup\Imoje\Payment\DTO\Concerns\HasMultipayout;
use Routegroup\Imoje\Payment\DTO\Concerns\HasVisibleMethod;
use Routegroup\Imoje\Payment\DTO\Concerns\ValidatesGatewayFields;
use Routegroup\Imoje\Payment\Factories\Api\PaymentDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

/**
 * @phpstan-import-type CustomerShape from CustomerDto
 *
 * @property-read string $serviceId
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $orderId
 * @property-read CustomerDto $customer
 * @property-read string $title
 * @property-read list<string> $visibleMethod
 * @property-read PaymentMethodCode|string $preselectMethodCode
 * @property-read string $returnUrl
 * @property-read string $successReturnUrl
 * @property-read string $failureReturnUrl
 * @property-read string $simp
 * @property-read int $validTo
 * @property-read CartDto|null $cart
 *
 * @method static PaymentDtoFactory factory($count = null, $state = [])
 */
class PaymentDto extends BaseDto
{
    use HasFactory;
    use HasMultipayout;
    use HasVisibleMethod;
    use ValidatesGatewayFields;

    protected bool $strictEnums = true;

    protected bool $allowNull = true;

    protected array $casts = [
        'amount' => 'int',
        'currency' => Currency::class,
        'cart' => CartDto::class,
        'invoice' => InvoiceDto::class,
        'isKsef' => 'bool',
        'customer' => CustomerDto::class,
    ];

    /**
     * @param array{
     *     // Required
     *     amount?: int,
     *     currency?: Currency|string,
     *     orderId?: string,
     *     customer?: CustomerDto|CustomerShape,
     *     // Required but provided
     *     serviceId?: string,
     *     // Optional
     *     title?: string,
     *     visibleMethod?: list<PaymentMethod|string>,
     *     preselectMethodCode?: PaymentMethodCode|string,
     *     returnUrl?: string,
     *     successReturnUrl?: string,
     *     failureReturnUrl?: string,
     *     simp?: string,
     *     validTo?: int,
     *     cart?: CartDto|array<mixed>,
     *     multipayout?: array<mixed>,
     *     invoice?: InvoiceDto|array<mixed>,
     *     isKsef?: bool,
     * } $attributes
     */
    public function __construct(array $attributes = [])
    {
        $config = app(Config::class);

        $attributes = array_merge([
            'serviceId' => $config->serviceId,
        ], $attributes);

        self::checkGatewayFields($attributes, [
            'orderId' => 'orderId',
            'title' => 'description',
        ]);

        parent::__construct(self::castVisibleMethod(self::castMultipayout($attributes)));
    }

    /** The payment-link schema is the one place multipayout stays at the top level. */
    public function toArray(): array
    {
        return $this->flattenMultipayout(parent::toArray());
    }

    protected static function newFactory(): PaymentDtoFactory
    {
        return PaymentDtoFactory::new();
    }
}
