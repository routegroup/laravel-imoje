<?php

namespace Routegroup\Imoje\Payment\Tests\Lib;

use Routegroup\Imoje\Payment\Exceptions\InvalidEnvironmentException;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\Environment;

function imojeConfig(array $overrides = []): array
{
    return array_merge([
        'merchant_id' => '$merchant_id$',
        'service_id' => '$service_id$',
        'service_key' => '$service_key$',
        'api_key' => '$api_key$',
        'env' => Environment::SANDBOX->value,
    ], $overrides);
}

it('falls back to production when the environment is left empty', function (mixed $value): void {
    config()->set('services.imoje', imojeConfig(['env' => $value]));

    expect(app(Config::class)->env)->toBe(Environment::PRODUCTION);
})->with([
    'empty string (what README produces)' => [''],
    'null' => [null],
    'whitespace' => ['   '],
]);

it('falls back to production when the key is missing entirely', function (): void {
    $config = imojeConfig();
    unset($config['env']);

    config()->set('services.imoje', $config);

    expect(app(Config::class)->env)->toBe(Environment::PRODUCTION);
});

it('still resolves an explicit environment', function (Environment $environment): void {
    config()->set('services.imoje', imojeConfig(['env' => $environment->value]));

    expect(app(Config::class)->env)->toBe($environment);
})->with([
    [Environment::SANDBOX],
    [Environment::PRODUCTION],
]);

it('rejects an invalid environment with a message naming the config key', function (): void {
    config()->set('services.imoje', imojeConfig(['env' => 'staging']));

    expect(fn () => app(Config::class))
        ->toThrow(
            InvalidEnvironmentException::class,
            'Invalid [services.imoje.env] value [staging]. Allowed values: production, sandbox.'
        );
});

it('carries the rejected value for logging', function (): void {
    config()->set('services.imoje', imojeConfig(['env' => 'staging']));

    try {
        app(Config::class);
    } catch (InvalidEnvironmentException $e) {
        expect($e->given)->toBe('staging')
            ->and($e->context())->toBe([
                'given' => 'staging',
                'allowed' => ['production', 'sandbox'],
            ]);

        return;
    }

    $this->fail('InvalidEnvironmentException was not thrown.');
});

it('is catchable as a standard invalid argument', function (): void {
    config()->set('services.imoje', imojeConfig(['env' => 'staging']));

    expect(fn () => app(Config::class))->toThrow(\InvalidArgumentException::class);
});
