<?php

namespace Routegroup\Imoje\Payment\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Orchestra\Testbench\TestCase as Orchestra;
use Routegroup\Imoje\Payment\ImojeServiceProvider;
use Routegroup\Imoje\Payment\Types\Environment;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'VendorName\\Skeleton\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            ImojeServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        $app->useEnvironmentPath(__DIR__.'/..');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);

        config()->set('database.default', 'testing');

        config()->set('services.imoje', [
            'merchant_id' => '$merchant_id$',
            'service_id' => '$service_id$',
            'service_key' => '$service_key$',
            'api_key' => '$api_key$',
            'env' => Environment::SANDBOX->value,
        ]);

        parent::getEnvironmentSetUp($app);
    }
}
