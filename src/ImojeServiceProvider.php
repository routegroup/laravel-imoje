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
use ValueError;

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
                    $this->resolveEnvironment($config->get('services.imoje.env')),
                );
            }
        );
    }

    /**
     * An empty `services.imoje.env` is as good as an absent one.
     *
     * README tells integrators to put `IMOJE_ENV=` in .env and read it with
     * env(), which yields an empty string rather than null, so the default
     * argument of Arr::get never applies and Environment::from('') used to
     * throw a bare \ValueError while the container was still booting.
     */
    private function resolveEnvironment(mixed $value): Environment
    {
        if (blank($value)) {
            return Environment::PRODUCTION;
        }

        return Environment::tryFrom((string) $value) ?? throw new ValueError(sprintf(
            'Invalid [services.imoje.env] value [%s]. Allowed values: %s.',
            (string) $value,
            implode(', ', array_column(Environment::cases(), 'value')),
        ));
    }

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
