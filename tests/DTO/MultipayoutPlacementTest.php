<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;

function multipayout(): array
{
    return [
        ['ban' => '72105000028166973380325415', 'amount' => 100, 'label' => 'Nazwa firmy 0'],
        ['ban' => '58105000025503268251444948', 'amount' => 200, 'label' => 'Nazwa firmy 1'],
    ];
}

it('sends multipayout under data, where the transaction schema wants it', function (): void {
    $payload = (new TransactionDto(['multipayout' => multipayout()]))->toArray();

    expect($payload)->not->toHaveKey('multipayout')
        ->and($payload['data']['multipayout'])->toBe(multipayout());
});

it('keeps other data keys when nesting multipayout', function (): void {
    $payload = (new TransactionDto([
        'data' => ['somethingElse' => 'kept'],
        'multipayout' => multipayout(),
    ]))->toArray();

    expect($payload['data'])->toBe([
        'somethingElse' => 'kept',
        'multipayout' => multipayout(),
    ]);
});

it('leaves an explicit data.multipayout alone', function (): void {
    $payload = (new TransactionDto([
        'data' => ['multipayout' => multipayout()],
    ]))->toArray();

    expect($payload)->not->toHaveKey('multipayout')
        ->and($payload['data']['multipayout'])->toBe(multipayout());
});

it('adds no data key when multipayout is absent', function (): void {
    $payload = (new TransactionDto(['amount' => 1000]))->toArray();

    expect($payload)->not->toHaveKey('data')
        ->and($payload)->not->toHaveKey('multipayout');
});

it('still exposes multipayout as a property', function (): void {
    $dto = new TransactionDto(['multipayout' => multipayout()]);

    expect($dto->multipayout)->toBe(multipayout());
});
