<?php

namespace Routegroup\Payment\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Routegroup\Payment\ImojeServiceProvider;

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
