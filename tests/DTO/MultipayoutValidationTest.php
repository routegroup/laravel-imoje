<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\ChargeProfileDto;
use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\MultipayoutDto;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto as PaywallTransactionDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidMultipayoutException;
use Routegroup\Imoje\Payment\Lib\Utils;

function entry(array $overrides = []): array
{
    return array_merge([
        'ban' => '72105000028166973380325415',
        'amount' => 100,
        'label' => 'Nazwa firmy 0',
    ], $overrides);
}

it('accepts the vector from the specification', function (): void {
    $list = MultipayoutDto::fromList([
        entry(),
        entry(['ban' => '58105000025503268251444948', 'amount' => 200, 'label' => 'Nazwa firmy 1']),
    ]);

    expect($list)->toHaveCount(2)
        ->and($list[1]->amount)->toBe(200)
        ->and($list[1]->title)->toBeNull();
});

it('rejects a ban that is not 26 digits', function (mixed $ban): void {
    expect(fn () => MultipayoutDto::fromList([entry(['ban' => $ban])]))
        ->toThrow(InvalidMultipayoutException::class);
})->with([
    'too short' => ['7210500002816697338032541'],
    'too long' => ['721050000281669733803254151'],
    'not digits' => ['PL10500002816697338032541x'],
    'empty' => [''],
    'missing' => [null],
]);

it('rejects an amount below one', function (mixed $amount): void {
    expect(fn () => MultipayoutDto::fromList([entry(['amount' => $amount])]))
        ->toThrow(InvalidMultipayoutException::class);
})->with([[0], [-5], ['abc'], [null]]);

it('rejects a label the gateway would not accept', function (mixed $label): void {
    expect(fn () => MultipayoutDto::fromList([entry(['label' => $label])]))
        ->toThrow(InvalidMultipayoutException::class);
})->with([
    'too long' => [str_repeat('a', 71)],
    'forbidden character' => ['Nazwa@firmy'],
    'empty' => [''],
]);

it('accepts diacritics in a label', function (): void {
    expect(MultipayoutDto::fromList([entry(['label' => 'Firma Żółć ĄĘ'])])[0]->label)
        ->toBe('Firma Żółć ĄĘ');
});

it('requires a title for every recipient once one has it', function (): void {
    expect(fn () => MultipayoutDto::fromList([
        entry(['title' => 'Platnosc 1']),
        entry(['ban' => '58105000025503268251444948']),
    ]))->toThrow(InvalidMultipayoutException::class, 'is required once any recipient has one');
});

it('accepts a title on every recipient', function (): void {
    $list = MultipayoutDto::fromList([
        entry(['title' => 'Platnosc 1']),
        entry(['ban' => '58105000025503268251444948', 'title' => 'Platnosc 2']),
    ]);

    expect($list[0]->title)->toBe('Platnosc 1')
        ->and($list[1]->title)->toBe('Platnosc 2');
});

it('names the offending recipient and field', function (): void {
    try {
        MultipayoutDto::fromList([entry(), entry(['ban' => 'nope'])]);
    } catch (InvalidMultipayoutException $e) {
        expect($e->index)->toBe(1)
            ->and($e->field)->toBe('ban')
            ->and($e->getMessage())->toContain('multipayout[1][ban]');

        return;
    }

    $this->fail('InvalidMultipayoutException was not thrown.');
});

it('serialises under data for a transaction', function (): void {
    $payload = (new TransactionDto(['multipayout' => [entry()]]))->toArray();

    expect($payload)->not->toHaveKey('multipayout')
        ->and($payload['data']['multipayout'][0])->toBe(entry());
});

it('serialises under data for a profile charge', function (): void {
    $payload = (new ChargeProfileDto(['multipayout' => [entry()]]))->toArray();

    expect($payload)->not->toHaveKey('multipayout')
        ->and($payload['data']['multipayout'][0])->toBe(entry());
});

it('serialises at the top level for a payment link', function (): void {
    $payload = (new PaymentDto(['amount' => 1000, 'multipayout' => [entry()]]))->toArray();

    expect($payload)->not->toHaveKey('data')
        ->and($payload['multipayout'][0])->toBe(entry());
});

it('signs a paywall transaction over the expanded recipients', function (): void {
    $dto = new PaywallTransactionDto(['amount' => 1000, 'multipayout' => [entry()]]);
    $payload = $dto->toArray();

    expect($payload['multipayout'][0])->toBe(entry());

    $signed = $payload;
    unset($signed['signature']);

    [$hash] = explode(';', (string) $payload['signature']);

    expect($hash)->toBe(
        hash('sha256', app(Utils::class)->buildQuery($signed).config('services.imoje.service_key'))
    );
});
