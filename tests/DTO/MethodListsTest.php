<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Casts\ServiceDataDto;
use Routegroup\Imoje\Payment\DTO\Casts\ServicePaymentMethodDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionLimitsDto;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

/** One entry as the sandbox sends it, random three-character keys included. */
function serviceMethodRow(array $overrides = []): array
{
    return array_replace([
        'paymentMethod' => 'pbl',
        'paymentMethodCode' => 'mtransfer',
        'isActive' => true,
        'isOnline' => true,
        'description' => 'mTransfer',
        'currency' => 'PLN',
        'transactionLimits' => [
            'maxTransaction' => ['type' => 'number', 'value' => 99999999, 'XK4' => '849'],
            'minTransaction' => ['type' => 'number', 'value' => 37, 'hPe' => 'lkf'],
            '2Be' => 'lUL',
        ],
        'paymentMethodImage' => [['png' => 'https://data.imoje.pl/img/pay/pbl.png', 'Har' => 'XZc']],
        'paymentMethodCodeImage' => [['png' => 'https://data.imoje.pl/img/pay/mtransfer.png']],
        'JZY' => 'abc',
    ], $overrides);
}

function serviceData(?array $methods = null): ServiceDataDto
{
    return new ServiceDataDto([
        'id' => 'svc-1',
        'isActive' => true,
        'paymentMethods' => $methods ?? [serviceMethodRow()],
    ]);
}

it('types every channel a service reports', function (): void {
    $methods = serviceData()->paymentMethods;

    expect($methods)->toHaveCount(1)
        ->and($methods[0])->toBeInstanceOf(ServicePaymentMethodDto::class)
        ->and($methods[0]->paymentMethod)->toBe(PaymentMethod::PAY_BY_LINK)
        ->and($methods[0]->paymentMethodCode)->toBe(PaymentMethodCode::MTRANSFER)
        ->and($methods[0]->currency)->toBe(Currency::PLN);
});

/** Chapter 05 points at this flag for avoiding a 406; #80 asked for typed access to it. */
it('gives typed access to the flag that avoids a 406', function (): void {
    $method = serviceData([serviceMethodRow(['isOnline' => false])])->paymentMethods[0];

    expect($method->isOnline)->toBeFalse()
        ->and($method->isActive)->toBeTrue();
});

/** A bare 0 or 1 is still a yes or no, and the cast is what makes it one. */
it('reads the flags as booleans whatever shape they arrive in', function (): void {
    $method = serviceData([serviceMethodRow(['isOnline' => 0, 'isActive' => 1])])->paymentMethods[0];

    expect($method->isOnline)->toBeFalse()
        ->and($method->isActive)->toBeTrue();
});

it('types the transaction limits', function (): void {
    $limits = serviceData()->paymentMethods[0]->transactionLimits;

    expect($limits)->toBeInstanceOf(TransactionLimitsDto::class)
        ->and($limits->minTransaction->value)->toBe(37)
        ->and($limits->maxTransaction->value)->toBe(99999999)
        ->and($limits->minTransaction->type)->toBe('number');
});

it('casts a limit the gateway sent as a string', function (): void {
    $limits = serviceData([serviceMethodRow(['transactionLimits' => [
        'minTransaction' => ['type' => 'number', 'value' => '37'],
    ]])])->paymentMethods[0]->transactionLimits;

    expect($limits->minTransaction->value)->toBe(37);
});

it('keeps the junk keys the gateway injects', function (): void {
    $payload = serviceData()->toArray()['paymentMethods'][0];

    expect($payload)->toHaveKey('JZY')
        ->and($payload['transactionLimits'])->toHaveKey('2Be');
});

it('survives a service with no channels at all', function (): void {
    expect(serviceData([])->paymentMethods)->toBe([]);
});

it('builds a service channel from its factory', function (): void {
    foreach (range(1, 10) as $ignored) {
        $method = ServicePaymentMethodDto::factory()->make();

        expect($method->isOnline)->toBeBool()
            ->and($method->transactionLimits->minTransaction->value)
            ->toBeLessThan($method->transactionLimits->maxTransaction->value);
    }
});
