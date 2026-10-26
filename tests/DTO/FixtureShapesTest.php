<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Casts\ActionDto;
use Routegroup\Imoje\Payment\DTO\Responses\ServicesResponseDto;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Types\ActionType;

it('builds a wire transfer action the way the gateway sends one', function (): void {
    $action = ActionDto::factory()->wireTransfer()->make();

    expect($action->type)->toBe(ActionType::TRANSFER)
        ->and($action->ban)->toMatch('/^[0-9]{26}$/')
        ->and($action->title)->toBeString()
        ->and(array_keys($action->toArray()))->toBe(['type', 'ban', 'title']);
});

it('leaves a redirect action carrying the redirect fields', function (): void {
    $action = ActionDto::factory()->make();

    expect($action->type)->toBe(ActionType::REDIRECT)
        ->and($action->url)->toBeString()
        ->and($action->method)->toBe('GET')
        ->and($action->ban)->toBeNull();
});

it('builds the services list as the top-level list the gateway sends', function (): void {
    $raw = ServicesResponseDto::factory()->make()->toArray();

    expect($raw['services'])->toHaveCount(1);
});

it('unwraps a real services payload through the api', function (): void {
    $api = app(Api::class);

    Http::fake([
        $api->url->createServicesUrl() => Http::response([
            ['service' => ['id' => 'svc-1', 'isActive' => true]],
            ['service' => ['id' => 'svc-2', 'isActive' => false]],
        ]),
    ]);

    $services = $api->getServices()->services;

    expect($services)->toHaveCount(2)
        ->and($services[0]->id)->toBe('svc-1')
        ->and($services[1]->id)->toBe('svc-2');
});

it('yields no services when the body is not the list the gateway sends', function (): void {
    $api = app(Api::class);

    Http::fake([
        $api->url->createServicesUrl() => Http::response(['services' => []]),
    ]);

    expect($api->getServices()->services)->toBe([]);
});
