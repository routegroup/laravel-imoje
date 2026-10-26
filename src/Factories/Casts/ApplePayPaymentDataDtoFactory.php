<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\ApplePayHeaderDto;
use Routegroup\Imoje\Payment\DTO\Casts\ApplePayPaymentDataDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ApplePayPaymentDataDtoFactory extends Factory
{
    protected $model = ApplePayPaymentDataDto::class;

    public function definition(): array
    {
        return [
            'version' => $this->faker->randomElement(['EC_v1', 'RSA_v1']),
            'data' => base64_encode($this->faker->text(400)),
            'signature' => base64_encode($this->faker->text(600)),
            'header' => ApplePayHeaderDto::factory()->make()->toArray(),
        ];
    }
}
