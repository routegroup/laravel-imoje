<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\ApplePayHeaderDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ApplePayHeaderDtoFactory extends Factory
{
    protected $model = ApplePayHeaderDto::class;

    public function definition(): array
    {
        return [
            'ephemeralPublicKey' => base64_encode($this->faker->sha256.$this->faker->sha256),
            'publicKeyHash' => base64_encode((string) hex2bin($this->faker->sha256)),
            // Apple sends a 64-character hex digest, which is also the gateway's minimum.
            'transactionId' => $this->faker->sha256,
        ];
    }
}
