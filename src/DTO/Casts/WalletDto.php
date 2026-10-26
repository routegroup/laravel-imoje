<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidWalletException;
use Routegroup\Imoje\Payment\Factories\Casts\WalletDtoFactory;

/**
 * One field carries both providers. Apple Pay sends paymentData and nothing
 * else; Google Pay sends the three token fields. paymentData picks the variant.
 *
 * @property-read string|null $signature
 * @property-read string|null $protocolVersion
 * @property-read string|null $signedMessage
 * @property-read ApplePayPaymentDataDto|null $paymentData
 *
 * @method static WalletDtoFactory factory($count = null, $state = [])
 */
class WalletDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'paymentData' => ApplePayPaymentDataDto::class,
    ];

    /** @throws InvalidWalletException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        if (($attributes['paymentData'] ?? null) === null) {
            foreach (['signature', 'protocolVersion', 'signedMessage'] as $field) {
                if (($attributes[$field] ?? null) === null || $attributes[$field] === '') {
                    throw new InvalidWalletException("wallet.$field", 'is required');
                }
            }
        }

        parent::__construct($attributes);
    }

    protected static function newFactory(): WalletDtoFactory
    {
        return WalletDtoFactory::new();
    }
}
