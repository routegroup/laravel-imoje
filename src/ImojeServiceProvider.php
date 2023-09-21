<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Environment;

class ImojeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            Utils::class,
            function (Application $app) {
                $config = $app->make('config');

                return new Utils(
                    $config->get('services.imoje.merchant_id'),
                    $config->get('services.imoje.service_id'),
                    $config->get('services.imoje.service_key'),
                    $config->get('services.imoje.api_key'),
                    Environment::from($config->get('services.imoje.env', Environment::PRODUCTION->value)),
                );
            }
        );

        $this->app->bind(
            Api::class,
            fn (Application $app) => new Api($app->make(Utils::class))
        );
    }

    /**
     * {@inheritDoc}
     */
    public function provides(): array
    {
        return [
            Api::class,
            Utils::class,
        ];
    }
}
