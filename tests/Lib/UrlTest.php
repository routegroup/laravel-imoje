<?php

use Routegroup\Imoje\Payment\Lib\Url;

beforeEach(function (): void {
    $this->url = app(Url::class);
});

it('creates refund url')
    ->expect(fn () => $this->url->createRefundUrl('$transaction_id$'))
    ->toEqual('https://sandbox.api.imoje.pl/v1/merchant/$merchant_id$/transaction/$transaction_id$/refund');

it('creates charge profile url')
    ->expect(fn () => $this->url->createChargeProfileUrl())
    ->toEqual('https://sandbox.api.imoje.pl/v1/merchant/$merchant_id$/transaction/profile');

it('creates profile id url')
    ->expect(fn () => $this->url->createProfileIdUrl('$profile_id$'))
    ->toEqual('https://sandbox.api.imoje.pl/v1/merchant/$merchant_id$/profile/id/$profile_id$');
