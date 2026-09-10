<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\BillingDto;
use Routegroup\Imoje\Payment\DTO\Casts\CardDto;
use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\Factories\Api\TransactionDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;
use Routegroup\Imoje\Payment\Types\TransactionType;

/**
 * @phpstan-import-type CustomerShape from CustomerDto
 * @phpstan-import-type BillingShape from BillingDto
 * @phpstan-import-type CardShape from CardDto
 *
 * @property-read TransactionType $type
 * @property-read string $serviceId
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $orderId
 * @property-read PaymentMethod $paymentMethod
 * @property-read PaymentMethodCode $paymentMethodCode
 * @property-read string $successReturnUrl
 * @property-read string $failureReturnUrl
 * @property-read CustomerDto $customer
 * @property-read string $title
 * @property-read BillingDto $billing
 * @property-read BillingDto $shipping
 * @property-read CardDto $card
 * @property-read array $visibleMethod
 * @property-read array $additionalData
 * @property-read int $validTo
 * @property-read array $multipayout
 * @property-read array $invoice
 *
 * @method static TransactionDtoFactory factory($count = null, $state = [])
 */
class TransactionDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'type' => TransactionType::class,
        'amount' => 'int',
        'currency' => Currency::class,
        'paymentMethod' => PaymentMethod::class,
        'paymentMethodCode' => PaymentMethodCode::class,
        'customer' => CustomerDto::class,
        'billing' => BillingDto::class,
        'shipping' => BillingDto::class,
        'card' => CardDto::class,
        // 'additionalData' => 'array' @todo
        // 'multipayout' => 'array' @todo
        // 'invoice' => 'array' @todo
    ];

    /**
     * @param array{
     *     // Required
     *     amount?: int,
     *     currency?: Currency|string,
     *     orderId?: string,
     *     paymentMethod?: PaymentMethod|string,
     *     paymentMethodCode?: PaymentMethodCode|string,
     *     successReturnUrl?: string,
     *     failureReturnUrl?: string,
     *     customer?: CustomerDto|CustomerShape,
     *     // Required but provided
     *     type?: TransactionType|string,
     *     serviceId?: string,
     *     // Optional
     *     title?: string,
     *     visibleMethod?: list<string>,
     *     billing?: BillingDto|BillingShape,
     *     shipping?: BillingDto|BillingShape,
     *     card?: CardDto|CardShape,
     *     additionalData?: array<mixed>,
     *     validTo?: int,
     *     multipayout?: array<mixed>,
     *     invoice?: array<mixed>,
     * } $attributes
     */
    public function __construct(array $attributes = [])
    {
        $config = app(Config::class);

        $attributes = array_merge_recursive([
            'type' => TransactionType::SALE,
            'serviceId' => $config->serviceId,
        ], $attributes);

        parent::__construct($attributes);
    }

    protected static function newFactory(): TransactionDtoFactory
    {
        return TransactionDtoFactory::new();
    }
}
