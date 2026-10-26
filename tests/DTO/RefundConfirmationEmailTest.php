<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\RefundDto;
use Routegroup\Imoje\Payment\Types\TransactionType;

it('omits the confirmation e-mail flag when the caller did not pass it', function (): void {
    $payload = (new RefundDto(['amount' => 1000]))->toArray();

    expect($payload)->not->toHaveKey('sendRefundConfirmationEmail');
});

it('sends the confirmation e-mail flag when it was passed explicitly', function (bool $value): void {
    $payload = (new RefundDto([
        'amount' => 1000,
        'sendRefundConfirmationEmail' => $value,
    ]))->toArray();

    expect($payload['sendRefundConfirmationEmail'])->toBe($value);
})->with([
    'disabled' => [false],
    'enabled' => [true],
]);

it('casts a truthy non-boolean flag', function (): void {
    $payload = (new RefundDto([
        'amount' => 1000,
        'sendRefundConfirmationEmail' => 1,
    ]))->toArray();

    expect($payload['sendRefundConfirmationEmail'])->toBeTrue();
});

it('reads back as null when the flag was never set', function (): void {
    expect((new RefundDto(['amount' => 1000]))->sendRefundConfirmationEmail)->toBeNull();
});

it('keeps the other refund defaults intact', function (): void {
    $payload = (new RefundDto(['amount' => 1000]))->toArray();

    expect($payload['type'])->toBe(TransactionType::REFUND->value)
        ->and($payload['amount'])->toBe(1000)
        ->and($payload['serviceId'])->toBe(config('services.imoje.service_id'));
});
