<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\ShippingDto;

class ShippingDtoFactory extends BillingDtoFactory
{
    protected $model = ShippingDto::class;

    public function definition(): array
    {
        $attributes = parent::definition();
        unset($attributes['taxId']);

        return $attributes;
    }
}
