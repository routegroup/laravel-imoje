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

    public function definition(): array
    {
        return [
            'invoiceId' => '04/12/23',
            'buyer' => InvoiceBuyerDto::factory()->make()->toArray(),
            'positions' => [InvoicePositionDto::factory()->make()->toArray()],
        ];
    }
}
