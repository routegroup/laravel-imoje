<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;

class PaymentDtoFactory extends Factory
{
    protected $model = PaymentDto::class;

    public function definition(): array
    {
        $now = now();

        return [
            'id' => $this->faker->unique()->uuid,
            'url' => "https://sandbox.paywall.imoje.pl/pay/{$this->faker->uuid}",
            'serviceId' => config('services.imoje.service_key'),
            'orderId' => $this->faker->unique()->uuid,
            'title' => $this->faker->unique()->uuid,
            'simp' => '',
            'amount' => $this->faker->numberBetween(1, 1000) * 100,
            'currency' => Currency::PLN,
            'returnUrl' => 'https://imoje.requestcatcher.com',
            'successReturnUrl' => 'https://imoje.requestcatcher.com',
            'failureReturnUrl' => 'https://imoje.requestcatcher.com',
            'customer' => CustomerDto::factory(),
            'isActive' => true,
            'validTo' => null,
            'created' => $now->timestamp,
            'modified' => $now->timestamp,
        ];
    }
}
