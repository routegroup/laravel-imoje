<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Tests\Services;

use Illuminate\Http\Request;
use Mockery;
use Routegroup\Imoje\Payment\Exceptions\NotImplementedException;
use Routegroup\Imoje\Payment\Lib\Validator;
use Routegroup\Imoje\Payment\Services\NotificationService;

beforeEach(function (): void {
    $validator = Mockery::mock(Validator::class);
    $validator
        ->expects('fromNotification')
        ->andReturnTrue();

    $this->service = app(NotificationService::class, [
        'validator' => $validator,
    ]);
});

function makeRequest(array $data = []): Request
{
    return new Request([], $data);
}

it('throws an exception when it doesnt match any type of scenario', function (): void {
    $this->service->resolve(makeRequest());
})->throws(NotImplementedException::class);
