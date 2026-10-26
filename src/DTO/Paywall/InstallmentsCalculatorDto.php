<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Paywall;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidInstallmentException;
use Routegroup\Imoje\Payment\Factories\Paywall\InstallmentsCalculatorDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\HashMethod;

/**
 * Options for the instalment calculator widget, which the shop renders into an
 * element with id `imoje-installments__wrapper`.
 *
 * @property-read string $merchantId
 * @property-read string $serviceId
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $signature
 *
 * @method static InstallmentsCalculatorDtoFactory factory($count = null, $state = [])
 */
class InstallmentsCalculatorDto extends BaseDto
{
    use HasFactory;

    public const MIN_AMOUNT = 30000;

    public const MAX_AMOUNT = 5000000;

    public const SCRIPT_ID = 'imoje-installments__script';

    public const WRAPPER_ID = 'imoje-installments__wrapper';

    protected array $casts = [
        'amount' => 'int',
        'currency' => Currency::class,
    ];

    /**
     * @param  array{amount?: int, currency?: Currency|string, serviceId?: string, merchantId?: string}  $attributes
     *
     * @throws InvalidInstallmentException
     */
    public function __construct($attributes = [], HashMethod $hashMethod = HashMethod::SHA256)
    {
        $config = app(Config::class);
        $utils = app(Utils::class);

        $attributes = array_merge([
            'serviceId' => $config->serviceId,
            'merchantId' => $config->merchantId,
            'currency' => Currency::PLN,
        ], (array) $attributes);

        $amount = $attributes['amount'] ?? null;

        if (! is_numeric($amount) || $amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            throw new InvalidInstallmentException(
                $amount,
                'calculator amount must be between '.self::MIN_AMOUNT.' and '.self::MAX_AMOUNT,
            );
        }

        $attributes['signature'] = $utils->createSignature($attributes, $hashMethod);

        parent::__construct($attributes);
    }

    protected static function newFactory(): InstallmentsCalculatorDtoFactory
    {
        return InstallmentsCalculatorDtoFactory::new();
    }
}
