<?php

use Illuminate\Http\Request;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Notifications\Paywall\OneClickNotificationDto;
use Routegroup\Imoje\Payment\Exceptions\NotImplementedException;
use Routegroup\Imoje\Payment\Lib\Validator;
use Routegroup\Imoje\Payment\Services\NotificationService;

beforeEach(function (): void {
    $this->service = app(NotificationService::class);
})->skip();

function mockRequest(BaseDto $dto): Request
{
    return new Request($dto->toArray());
}

it('throws exception when does not recognize notification type', function (): void {
    $validator = Mockery::mock(Validator::class);

    $validator
        ->allows('fromNotification')
        ->andReturnTrue();

    $service = app(NotificationService::class, [
        'validator' => $validator,
    ]);

    $service->resolve(new Request());
})->throws(NotImplementedException::class);

it('returns OneClickNotification', function (): void {
    $request = mockRequest(OneClickNotificationDto::factory()->make());
    $result = $this->service->resolve($request);
    expect($result)->toBeInstanceOf(OneClickNotificationDto::class);
});
