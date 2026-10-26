<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\InvoicePositionDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\TaxStake;

class InvoicePositionDtoFactory extends Factory
{
    protected $model = InvoicePositionDto::class;

    public function definition(): array
    {
        return [
            'name' => 'Produkt',
            'code' => 'produkt-01',
            'quantity' => 1,
            'unit' => 'Sztuki',
            'taxStake' => TaxStake::TAX_23,
            'grossAmount' => 12300,
            'taxAmount' => 2300,
        ];
    }
}
