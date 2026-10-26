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
            'signature' => base64_encode($this->faker->text(200)),
            'protocolVersion' => $this->faker->randomElement(['ECv1', 'ECv2']),
            'signedMessage' => json_encode([
                'encryptedMessage' => base64_encode($this->faker->text(300)),
                'ephemeralPublicKey' => base64_encode($this->faker->sha256),
                'tag' => base64_encode((string) hex2bin($this->faker->sha256)),
            ]),
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
