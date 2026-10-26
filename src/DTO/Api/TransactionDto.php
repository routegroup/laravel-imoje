<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\AdditionalDataDto;
use Routegroup\Imoje\Payment\DTO\Casts\BillingDto;
use Routegroup\Imoje\Payment\DTO\Casts\CardDto;
use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\DTO\Casts\InstallmentDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoiceDto;
use Routegroup\Imoje\Payment\DTO\Casts\ShippingDto;
use Routegroup\Imoje\Payment\DTO\Casts\WalletDto;
use Routegroup\Imoje\Payment\DTO\Concerns\HasMultipayout;
use Routegroup\Imoje\Payment\DTO\Concerns\HasVisibleMethod;
use Routegroup\Imoje\Payment\Factories\Api\TransactionDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;
use Routegroup\Imoje\Payment\Types\ProfileType;
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
 * @property-read PaymentMethodCode|null $paymentMethodCode
 * @property-read string $successReturnUrl
 * @property-read string $failureReturnUrl
 * @property-read CustomerDto $customer
 * @property-read string $title
 * @property-read BillingDto $billing
 * @property-read ShippingDto $shipping
 * @property-read CardDto $card
 * @property-read list<string> $visibleMethod
 * @property-read AdditionalDataDto|null $additionalData
 * @property-read WalletDto|null $wallet
 * @property-read int $validTo
 * @property-read string|null $notificationUrl
 * @property-read string|null $additionalDescription
 * @property-read string|null $blikCode
 * @property-read string|null $clientIp
 * @property-read ProfileType|null $profileType
 * @property-read array|null $data Serialised multipayout; see HasMultipayout
 * @property-read array $multipayout Serialised as data.multipayout; see toArray()
 * @property-read InstallmentDto|null $installment
 * @property-read InvoiceDto|null $invoice
 *
 * @method static TransactionDtoFactory factory($count = null, $state = [])
 */
class TransactionDto extends BaseDto
{
    use HasFactory;
    use HasMultipayout;
    use HasVisibleMethod;

    protected bool $strictEnums = true;

    protected bool $allowNull = true;

    protected array $casts = [
        'type' => TransactionType::class,
        'amount' => 'int',
        'currency' => Currency::class,
        'paymentMethod' => PaymentMethod::class,
        'paymentMethodCode' => PaymentMethodCode::class,
        'customer' => CustomerDto::class,
        'billing' => BillingDto::class,
        'shipping' => ShippingDto::class,
        'card' => CardDto::class,
        'installment' => InstallmentDto::class,
        'profileType' => ProfileType::class,
        'additionalData' => AdditionalDataDto::class,
        'wallet' => WalletDto::class,
        // 'multipayout' => 'array' @todo
        'invoice' => InvoiceDto::class,
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
     *     visibleMethod?: list<PaymentMethod|string>,
     *     billing?: BillingDto|BillingShape,
     *     shipping?: ShippingDto|BillingShape,
     *     card?: CardDto|CardShape,
     *     validTo?: int,
     *     notificationUrl?: string,
     *     additionalDescription?: string,
     *     blikCode?: string,
     *     clientIp?: string,
     *     profileType?: ProfileType|string,
     *     // Sent as data.multipayout, which is where the schema wants it.
     *     multipayout?: array<mixed>,
     *     installment?: InstallmentDto|array{period: int},
     *     invoice?: InvoiceDto|array<mixed>,
     *     wallet?: WalletDto|array<mixed>,
     *     additionalData?: AdditionalDataDto|array<mixed>,
     * } $attributes
     */
    public function __construct(array $attributes = [])
    {
        $config = app(Config::class);

        $attributes = array_merge([
            'type' => TransactionType::SALE,
            'serviceId' => $config->serviceId,
        ], $attributes);

        parent::__construct(self::castVisibleMethod(self::castMultipayout($attributes)));

        if ($this->installment instanceof InstallmentDto && $this->paymentMethodCode instanceof PaymentMethodCode) {
            $this->installment->assertFitsChannel($this->paymentMethodCode);
        }
    }

    /**
     * The transaction schema carries multipayout under `data`, with
     * `data.required: ["multipayout"]` — only the payment-link schema has it
     * at the top level. Accepting it flat keeps the documented input shape
     * and puts it where this endpoint expects it.
     */
    public function toArray(): array
    {
        $attributes = $this->flattenMultipayout(parent::toArray());

        if (! isset($attributes['multipayout'])) {
            return $attributes;
        }

        $multipayout = $attributes['multipayout'];

        unset($attributes['multipayout']);

        $data = $attributes['data'] ?? [];
        $data = is_array($data) ? $data : [];
        $data['multipayout'] = $multipayout;

        $attributes['data'] = $data;

        return $attributes;
    }

    protected static function newFactory(): TransactionDtoFactory
    {
        return TransactionDtoFactory::new();
    }
}
