<?php

use Routegroup\Imoje\Payment\Lib\Url;

beforeEach(function (): void {
    $this->url = app(Url::class);
});

$base = 'https://sandbox.api.imoje.pl/v1/merchant/$merchant_id$';

it('creates transaction url')
    ->expect(fn () => $this->url->createTransactionUrl())
    ->toEqual($base.'/transaction');

it('creates get transaction url')
    ->expect(fn () => $this->url->createGetTransactionUrl('$transaction_id$'))
    ->toEqual($base.'/transaction/$transaction_id$');

it('creates payment url')
    ->expect(fn () => $this->url->createPaymentUrl())
    ->toEqual($base.'/payment');

it('creates get payment url')
    ->expect(fn () => $this->url->createGetPaymentUrl('$payment_id$'))
    ->toEqual($base.'/payment/$payment_id$');

it('creates cancel payment url')
    ->expect(fn () => $this->url->createCancelPaymentUrl())
    ->toEqual($base.'/payment/cancel');

it('creates refund url')
    ->expect(fn () => $this->url->createRefundUrl('$transaction_id$'))
    ->toEqual($base.'/transaction/$transaction_id$/refund');

it('creates charge profile url')
    ->expect(fn () => $this->url->createChargeProfileUrl())
    ->toEqual($base.'/transaction/profile');

it('creates profile id url')
    ->expect(fn () => $this->url->createProfileIdUrl('$profile_id$'))
    ->toEqual($base.'/profile/id/$profile_id$');

it('creates capture url')
    ->expect(fn () => $this->url->createCaptureUrl('$transaction_id$'))
    ->toEqual($base.'/transaction/$transaction_id$/capture');

it('creates void url')
    ->expect(fn () => $this->url->createVoidUrl('$transaction_id$'))
    ->toEqual($base.'/transaction/$transaction_id$/void');

it('creates can refund url')
    ->expect(fn () => $this->url->createCanRefundUrl('$transaction_id$'))
    ->toEqual($base.'/transaction/$transaction_id$/can-refund');

it('creates profile cid url')
    ->expect(fn () => $this->url->createProfileCidUrl('$cid$'))
    ->toEqual($base.'/profile/cid/$cid$');

it('creates profile deactivate url')
    ->expect(fn () => $this->url->createProfileDeactivateUrl())
    ->toEqual($base.'/profile/deactivate');

it('creates service url')
    ->expect(fn () => $this->url->createServiceUrl('$service_id$'))
    ->toEqual($base.'/service/$service_id$');

it('creates services url')
    ->expect(fn () => $this->url->createServicesUrl())
    ->toEqual($base.'/services');

it('creates get payment methods url')
    ->expect(fn () => $this->url->createGetPaymentMethodsUrl('$service_id$'))
    ->toEqual($base.'/service/$service_id$/get-payment-methods');

it('creates settings ips url')
    ->expect(fn () => $this->url->createSettingsIpsUrl())
    ->toEqual($base.'/settings/ips');
