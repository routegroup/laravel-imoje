<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Lib\Url;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Environment;

class ImojeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            Config::class,
            function (Application $app) {
                $config = $app->make('config');

                return new Config(
                    $config->get('services.imoje.merchant_id'),
                    $config->get('services.imoje.service_id'),
                    $config->get('services.imoje.service_key'),
                    $config->get('services.imoje.api_key'),
                    Environment::from($config->get('services.imoje.env', Environment::PRODUCTION->value)),
                );
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function provides(): array
    {
        return [
            Config::class,
            Api::class,
            Url::class,
            Utils::class,
        ];
    }
}
