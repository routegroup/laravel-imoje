<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Illuminate\Http\Request;
use Routegroup\Imoje\Payment\DTO\Notifications\ReportNotificationDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidSignatureException;
use Routegroup\Imoje\Payment\Lib\Validator;

it('hydrates the documented report notification payload', function (): void {
    $dto = new ReportNotificationDto([
        'id' => '57526046-db6a-45f9-8288-284337129a8f',
        'status' => 'generated',
        'fileExt' => 'owi1',
        'created' => 1540382655,
        'modified' => 1705398343,
        'url' => 'https://pay.ing.pl/351e2776166edaf0e867e3231e518111b17580ba',
    ]);

    expect($dto->id)->toBe('57526046-db6a-45f9-8288-284337129a8f')
        ->and($dto->status)->toBe('generated')
        ->and($dto->fileExt)->toBe('owi1')
        ->and($dto->created)->toBe(1540382655)
        ->and($dto->modified)->toBe(1705398343)
        ->and($dto->url)->toStartWith('https://pay.ing.pl/');
});

it('casts report timestamps sent as strings', function (): void {
    $dto = new ReportNotificationDto([
        'created' => '1540382655',
        'modified' => '1705398343',
    ]);

    expect($dto->created)->toBeInt()
        ->and($dto->modified)->toBeInt();
});

it('builds a report notification from its factory', function (): void {
    $dto = ReportNotificationDto::factory()->make();

    expect($dto->toArray())
        ->toHaveKeys(['id', 'status', 'fileExt', 'created', 'modified', 'url'])
        ->and($dto->fileExt)->toBeIn(['csv', 'owi1', 'mt940']);
});

it('refuses to verify a report notification, which carries no signature', function (): void {
    $body = json_encode(ReportNotificationDto::factory()->make()->toArray());

    app(Validator::class)->verifySignature(
        Request::create('/report-notification', 'POST', [], [], [], [], $body)
    );
})->throws(InvalidSignatureException::class);
