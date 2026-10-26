<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Routegroup\Imoje\Payment\Exceptions\InvalidEnvironmentException;
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
                    $this->resolveEnvironment($config->get('services.imoje.env')),
                    (bool) $config->get('services.imoje.strict_errors', false),
                );
            }
        );
    }

    /** env('IMOJE_ENV') yields an empty string, not null, so Arr::get's default never applies. */
    private function resolveEnvironment(mixed $value): Environment
    {
        if (blank($value)) {
            return Environment::PRODUCTION;
        }

        return Environment::tryFrom((string) $value)
            ?? throw new InvalidEnvironmentException((string) $value);
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
