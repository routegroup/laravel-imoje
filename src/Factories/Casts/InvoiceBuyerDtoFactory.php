<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\InvoiceBuyerDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\InvoiceBuyerType;
use Routegroup\Imoje\Payment\Types\InvoiceIdType;

class InvoiceBuyerDtoFactory extends Factory
{
    protected $model = InvoiceBuyerDto::class;

    public function definition(): array
    {
        return [
            'type' => InvoiceBuyerType::PERSON,
            'email' => $this->faker->email,
            'fullName' => $this->faker->name,
            'street' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'postalCode' => $this->faker->numerify('##-###'),
            'countryCodeAlpha2' => 'PL',
        ];
    }

    public function company(): static
    {
        return $this->state(fn (): array => [
            'type' => InvoiceBuyerType::COMPANY,
            'idType' => InvoiceIdType::VAT_ID,
            'idNumber' => $this->faker->numerify('##########'),
            'idCountryCodeAlpha2' => 'PL',
        ]);
    }
}
