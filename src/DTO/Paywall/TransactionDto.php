<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Paywall;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\CartDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoiceDto;
use Routegroup\Imoje\Payment\DTO\Concerns\HasMultipayout;
use Routegroup\Imoje\Payment\DTO\Concerns\ValidatesGatewayFields;
use Routegroup\Imoje\Payment\Factories\Paywall\TransactionDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\HashMethod;

/**
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $orderId
 * @property-read string $customerId
 * @property-read string $customerFirstName
 * @property-read string $customerLastName
 * @property-read string $serviceId
 * @property-read string $merchantId
 * @property-read string $signature
 * @property-read string $customerEmail
 * @property-read string $customerPhone
 * @property-read string $urlSuccess
 * @property-read string $urlFailure
 * @property-read string $urlReturn
 * @property-read string $orderDescription
 * @property-read string $visibleMethod
 * @property-read int $validTo
 * @property-read string $cart Encoded by the constructor
 *
 * @method static TransactionDtoFactory factory($count = null, $state = [])
 */
class TransactionDto extends BaseDto
{
    use HasFactory;
    use HasMultipayout;
    use ValidatesGatewayFields;

    protected array $casts = [
        'amount' => 'int',
    ];

    /**
     * @param array{
     *     // Required
     *     amount?: int,
     *     currency?: Currency|string,
     *     orderId?: string,
     *     customerFirstName?: string,
     *     customerLastName?: string,
     *     // Required but provided
     *     serviceId?: string,
     *     merchantId?: string,
     *     signature?: string,
     *     // Optional
     *     customerEmail?: string,
     *     customerPhone?: string,
     *     urlSuccess?: string,
     *     urlFailure?: string,
     *     urlReturn?: string,
     *     orderDescription?: string,
     *     // Separated by comma
     *     visibleMethod?: string,
     *     invoice?: InvoiceDto|array<mixed>,
     *     cart?: CartDto|array<mixed>,
     *     validTo?: int,
     * } $attributes
     */
    public function __construct($attributes = [], HashMethod $hashMethod = HashMethod::SHA256)
    {
        $config = app(Config::class);
        $utils = app(Utils::class);

        $attributes = array_merge([
            'serviceId' => $config->serviceId,
            'merchantId' => $config->merchantId,
        ], $attributes);

        self::checkGatewayFields($attributes, [
            'orderId' => 'orderId',
            'orderDescription' => 'description',
            'customerFirstName' => 'name',
            'customerLastName' => 'name',
            'customerPhone' => 'phone',
            'customerId' => 'customerId',
            'customerEmail' => 'email',
        ]);

        $attributes = self::castMultipayout($attributes);

        if (isset($attributes['invoice'])) {
            $invoice = $attributes['invoice'];
            $attributes['invoice'] = $invoice instanceof InvoiceDto
                ? $invoice->encoded()
                : (is_array($invoice) ? (new InvoiceDto($invoice))->encoded() : $invoice);
        }

        if (isset($attributes['cart'])) {
            $cart = $attributes['cart'];
            $attributes['cart'] = $cart instanceof CartDto
                ? $cart->encoded()
                : (is_array($cart) ? (new CartDto($cart))->encoded() : $cart);
        }

        $attributes['signature'] = $utils->createSignature($attributes, $hashMethod);

        parent::__construct($attributes);
    }

    public function toArray(): array
    {
        return $this->flattenMultipayout(parent::toArray());
    }

    protected static function newFactory(): TransactionDtoFactory
    {
        return TransactionDtoFactory::new();
    }
}
