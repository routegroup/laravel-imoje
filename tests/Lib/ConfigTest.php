<?php

use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\Environment;

it('gets config from services.imoje', function (): void {
    $config = app(Config::class);

    expect($config)
        ->merchantId->toEqual(config('services.imoje.merchant_id'))
        ->serviceId->toEqual(config('services.imoje.service_id'))
        ->serviceKey->toEqual(config('services.imoje.service_key'))
        ->apiKey->toEqual(config('services.imoje.api_key'))
        ->env->toEqual(Environment::from(config('services.imoje.env')));
});
