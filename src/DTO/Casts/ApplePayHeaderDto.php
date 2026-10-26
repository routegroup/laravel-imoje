<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidWalletException;
use Routegroup\Imoje\Payment\Factories\Casts\ApplePayHeaderDtoFactory;

/**
 * @property-read string $ephemeralPublicKey
 * @property-read string $publicKeyHash
 * @property-read string $transactionId
 *
 * @method static ApplePayHeaderDtoFactory factory($count = null, $state = [])
 */
class ApplePayHeaderDto extends BaseDto
{
    use HasFactory;

    public const TRANSACTION_ID_LENGTH = 64;

    /** @throws InvalidWalletException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        foreach (['ephemeralPublicKey', 'publicKeyHash', 'transactionId'] as $field) {
            if (($attributes[$field] ?? null) === null || $attributes[$field] === '') {
                throw new InvalidWalletException("wallet.paymentData.header.$field", 'is required');
            }
        }

        if (strlen((string) $attributes['transactionId']) < self::TRANSACTION_ID_LENGTH) {
            throw new InvalidWalletException(
                'wallet.paymentData.header.transactionId',
                'must be at least '.self::TRANSACTION_ID_LENGTH.' characters',
            );
        }

        parent::__construct($attributes);
    }

    protected static function newFactory(): ApplePayHeaderDtoFactory
    {
        return ApplePayHeaderDtoFactory::new();
    }
}
