<?php

use Illuminate\Http\Request;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Notifications\OneClickNotificationDto;
use Routegroup\Imoje\Payment\Services\NotificationService;

beforeEach(function (): void {
    $this->service = app(NotificationService::class);
});

function mockRequest(BaseDto $dto): Request
{
    return new Request($dto->toArray());
}

it('returns OneClickNotification', function (): void {
    $request = mockRequest(OneClickNotificationDto::factory()->make());
    $result = $this->service->resolve($request);
    expect($result)->toBeInstanceOf(OneClickNotificationDto::class);
});
