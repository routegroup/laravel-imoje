<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\InvoiceBuyerDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoiceDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoicePositionDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class InvoiceDtoFactory extends Factory
{
    protected $model = InvoiceDto::class;

    /** invoiceId is set, so this is the Split Payment variant and positions carry taxAmount. */
    public function definition(): array
    {
        return [
            'invoiceId' => $this->faker->numerify('##/##/##'),
            'buyer' => InvoiceBuyerDto::factory()->make()->toArray(),
            'positions' => array_map(
                static fn (): array => InvoicePositionDto::factory()->make()->toArray(),
                range(1, $this->faker->numberBetween(1, 3)),
            ),
        ];
    }
}
