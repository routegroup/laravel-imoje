<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\PayerCardDataDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class PayerCardDataDtoFactory extends Factory
{
    protected $model = PayerCardDataDto::class;

    public function definition(): array
    {
        $country = $this->faker->randomElement([
            ['PL', 'POL', 'Poland'],
            ['DE', 'DEU', 'Germany'],
            ['GB', 'GBR', 'United Kingdom'],
        ]);

        return [
            'brand' => $this->faker->randomElement(['visa', 'mastercard', 'maestro']),
            'bin' => $this->faker->numerify('######'),
            'last4' => $this->faker->numerify('####'),
            'type' => $this->faker->randomElement(['debit', 'credit', 'prepaid']),
            'countryCodeAlpha2' => $country[0],
            'countryCodeAlpha3' => $country[1],
            'countryName' => $country[2],
            'firstTransactionId' => $this->faker->uuid,
        ];
    }
}
