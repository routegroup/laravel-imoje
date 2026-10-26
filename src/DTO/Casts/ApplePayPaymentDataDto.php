<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidWalletException;
use Routegroup\Imoje\Payment\Factories\Casts\ApplePayPaymentDataDtoFactory;

/**
 * @property-read string $version
 * @property-read string $data
 * @property-read string $signature
 * @property-read ApplePayHeaderDto $header
 *
 * @method static ApplePayPaymentDataDtoFactory factory($count = null, $state = [])
 */
class ApplePayPaymentDataDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'header' => ApplePayHeaderDto::class,
    ];

    /** @throws InvalidWalletException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        foreach (['version', 'data', 'signature', 'header'] as $field) {
            if (($attributes[$field] ?? null) === null || $attributes[$field] === '') {
                throw new InvalidWalletException("wallet.paymentData.$field", 'is required');
            }
        }

        parent::__construct($attributes);
    }

    protected static function newFactory(): ApplePayPaymentDataDtoFactory
    {
        return ApplePayPaymentDataDtoFactory::new();
    }
}
