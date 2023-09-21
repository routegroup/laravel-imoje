<?php

namespace Routegroup\Imoje\Payment\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Routegroup\Imoje\Payment\ImojeServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ImojeServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
