<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Illuminate\Support\HtmlString;
use Routegroup\Imoje\Payment\DTO\Paywall\OneClickDto;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\WidgetType;

/**
 * ING's own prepareData, transcribed from the Autoryzacja chapter. An
 * independent implementation of the same specification is the only check
 * available here: the widget is a script, so there is no endpoint to post to.
 */
function ingPrepareData(array $data, string $prefix = ''): string
{
    ksort($data);
    $hashData = [];

    foreach ($data as $key => $value) {
        if ($prefix !== '') {
            $key = $prefix.'['.$key.']';
        }

        $hashData[] = is_array($value)
            ? ingPrepareData($value, (string) $key)
            : $key.'='.$value;
    }

    return implode('&', $hashData);
}

function ingSignature(array $data): string
{
    $key = app(Config::class)->serviceKey;

    return hash('sha256', ingPrepareData($data).$key).';sha256';
}

function widgetInput(array $overrides = []): array
{
    return array_replace([
        'amount' => 200,
        'currency' => 'PLN',
        'orderId' => '123',
        'customerId' => '123',
        'customerFirstName' => 'John',
        'customerLastName' => 'Doe',
        'customerEmail' => 'johndoe@domain.com',
    ], $overrides);
}

/** What the DTO signs must match what ING's own snippet would have signed. */
function signedAs(array $input): string
{
    $config = app(Config::class);

    return ingSignature(array_diff_key(
        array_merge([
            'serviceId' => $config->serviceId,
            'merchantId' => $config->merchantId,
            'widgetType' => WidgetType::ONECLICK->value,
        ], $input),
        array_flip(OneClickDto::UNSIGNED),
    ));
}

it('signs what ING’s own algorithm would sign', function (): void {
    $input = widgetInput([
        'customerPhone' => '515515515',
        'orderDescription' => 'Example transaction',
        'urlSuccess' => 'https://shop.example/success',
        'validTo' => 1893456000,
    ]);

    expect((new OneClickDto($input))->signature)->toBe(signedAs($input));
});

it('leaves the signature untouched by a configuration parameter', function (string $field, mixed $value): void {
    $plain = new OneClickDto(widgetInput());
    $configured = new OneClickDto(widgetInput([$field => $value]));

    expect($configured->signature)->toBe($plain->signature);
})->with([
    'locale' => ['locale', 'en'],
    'inline' => ['inline', true],
    'element id' => ['elementId', 'pay-button'],
    'element event' => ['elementEvent', 'mouseover'],
]);

/** The control for the test above: a signed field must still move the hash. */
it('changes the signature when a signed parameter changes', function (): void {
    expect((new OneClickDto(widgetInput(['orderId' => 'a'])))->signature)
        ->not->toBe((new OneClickDto(widgetInput(['orderId' => 'b'])))->signature);
});

it('carries the id the widget refuses to start without', function (): void {
    $attributes = (new OneClickDto(widgetInput()))->attributes();

    expect($attributes['id'])->toBe('imoje-widget__script')
        ->and($attributes['src'])->toBe(app(Config::class)->env->widgetUrl());
});

it('names every attribute the way the widget reads it', function (): void {
    $attributes = (new OneClickDto(widgetInput([
        'orderDescription' => 'Opis',
        'urlSuccess' => 'https://shop.example/ok',
        'validTo' => 1893456000,
        'elementId' => 'pay-button',
        'elementEvent' => 'mouseover',
    ])))->attributes();

    expect(array_keys($attributes))->toContain(
        'data-merchant-id', 'data-service-id', 'data-order-id', 'data-customer-first-name',
        'data-customer-last-name', 'data-customer-email', 'data-order-description',
        'data-url-success', 'data-valid-to', 'data-widget-type', 'data-signature',
        'data-element-id', 'data-element-event',
    );
});

it('writes a boolean the way an HTML attribute wants it', function (): void {
    expect((new OneClickDto(widgetInput(['inline' => true])))->attributes()['data-inline'])->toBe('true');
});

/** The signature expands the array; the attribute carries JSON. */
it('renders multipayout as JSON while signing it expanded', function (): void {
    $multipayout = [[
        'ban' => '39109024028943913168514519',
        'amount' => 100,
        'label' => 'Test1',
    ]];

    $dto = new OneClickDto(widgetInput(['multipayout' => $multipayout]));

    expect(json_decode($dto->attributes()['data-multipayout'], true))->toBe($multipayout)
        ->and($dto->signature)->toBe(signedAs(widgetInput(['multipayout' => $multipayout])));
});

it('carries the invoice already encoded', function (): void {
    $dto = new OneClickDto(widgetInput(['invoice' => [
        'buyer' => [
            'type' => 'PERSON', 'email' => 'jan@example.com', 'fullName' => 'Jan Kowalski',
            'street' => 'Ulica', 'city' => 'Miasto', 'postalCode' => '12-345',
            'countryCodeAlpha2' => 'PL',
        ],
        'positions' => [[
            'name' => 'Produkt', 'code' => 'produkt-01', 'quantity' => 1,
            'unit' => 'Sztuki', 'taxStake' => 'TAX_23', 'grossAmount' => 12300,
        ]],
    ]]));

    expect(json_decode((string) gzdecode((string) base64_decode($dto->attributes()['data-invoice'])), true))
        ->toHaveKey('positions');
});

it('escapes a value that would otherwise end the attribute', function (): void {
    $script = (new OneClickDto(widgetInput(['orderDescription' => 'Say "hi" & run'])))->script();

    expect((string) $script)->toContain('data-order-description="Say &quot;hi&quot; &amp; run"')
        ->and($script)->toBeInstanceOf(HtmlString::class);
});

it('puts the extra attributes a page needs on the tag', function (): void {
    $script = (string) (new OneClickDto(widgetInput()))->script([
        'nonce' => 'abc123',
        'async' => true,
        'defer' => false,
    ]);

    expect($script)->toContain('nonce="abc123"')
        ->and($script)->toContain(' async')
        ->and($script)->not->toContain('defer');
});

it('serves all three widget types', function (string $type): void {
    $dto = new OneClickDto(widgetInput(['widgetType' => $type]));

    expect($dto->attributes()['data-widget-type'])->toBe($type);
})->with(['oneclick', 'recurring', 'ecom3ds']);
