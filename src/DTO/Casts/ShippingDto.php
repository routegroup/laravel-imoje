<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Routegroup\Imoje\Payment\Factories\Casts\ShippingDtoFactory;

/**
 * A shipping address is a billing address without taxId — the one field the
 * two schemas differ by. Sending it answers 422, so it is dropped here rather
 * than carried to the gateway.
 *
 * Extends BillingDto so anything already typed against that keeps working.
 *
 * @method static ShippingDtoFactory factory($count = null, $state = [])
 */
class ShippingDto extends BillingDto
{
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;
        unset($attributes['taxId']);

        parent::__construct($attributes);
    }

    protected static function newFactory(): ShippingDtoFactory
    {
        return ShippingDtoFactory::new();
    }
}
