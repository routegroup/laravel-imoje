<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoiceDto;
use Routegroup\Imoje\Payment\DTO\Concerns\HasMultipayout;
use Routegroup\Imoje\Payment\Factories\Api\ChargeProfileDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\Currency;

/**
 * @property-read string $serviceId
 * @property-read string $paymentProfileId
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $orderId
 * @property-read string|null $title
 * @property-read string|null $notificationUrl
 * @property-read array|null $data
 * @property-read InvoiceDto|null $invoice
 * @property-read array|null $multipayout Serialised as data.multipayout
 *
 * @method static ChargeProfileDtoFactory factory($count = null, $state = [])
 */
class ChargeProfileDto extends BaseDto
{
    use HasFactory;
    use HasMultipayout;

    protected bool $strictEnums = true;

    protected bool $allowNull = true;

    protected array $casts = [
        'amount' => 'int',
        'currency' => Currency::class,
        'invoice' => InvoiceDto::class,
    ];

    /**
     * @param array{
     *     // Required
     *     paymentProfileId?: string,
     *     amount?: int,
     *     currency?: Currency|string,
     *     orderId?: string,
     *     // Required but provided by default
     *     serviceId?: string,
     *     // Optional
     *     title?: string,
     *     notificationUrl?: string,
     *     // @todo: add proper DTO for data and invoice
     *     data?: array<mixed>,
     *     invoice?: InvoiceDto|array<mixed>,
     *     // Sent as data.multipayout, which is where the schema wants it.
     *     multipayout?: array<mixed>,
     * } $attributes
     */
    public function __construct(array $attributes = [])
    {
        $config = app(Config::class);

        $attributes = array_merge([
            'serviceId' => $config->serviceId,
        ], $attributes);

        parent::__construct(self::castMultipayout($attributes));
    }

    /** debit-profile carries multipayout under `data`, like the transaction schema. */
    public function toArray(): array
    {
        $attributes = $this->flattenMultipayout(parent::toArray());

        if (! isset($attributes['multipayout'])) {
            return $attributes;
        }

        $multipayout = $attributes['multipayout'];

        unset($attributes['multipayout']);

        $data = $attributes['data'] ?? [];
        $attributes['data'] = (is_array($data) ? $data : []) + ['multipayout' => $multipayout];

        return $attributes;
    }

    protected static function newFactory(): ChargeProfileDtoFactory
    {
        return ChargeProfileDtoFactory::new();
    }
}
