<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\ApplePayPaymentDataDto;
use Routegroup\Imoje\Payment\DTO\Casts\WalletDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class WalletDtoFactory extends Factory
{
    protected $model = WalletDto::class;

    /** Google Pay by default; applePay() swaps in the other provider's shape. */
    public function definition(): array
    {
        return [
            'signature' => 'signature',
            'protocolVersion' => 'ECv2',
            'signedMessage' => 'signedMessage',
        ];
    }

    public function applePay(): self
    {
        return $this->state(fn (): array => [
            'signature' => null,
            'protocolVersion' => null,
            'signedMessage' => null,
            'paymentData' => ApplePayPaymentDataDto::factory()->make()->toArray(),
        ]);
    }
}
