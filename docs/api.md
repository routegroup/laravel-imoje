# API

Reference documentation: [ING Pay RESTful API](https://bump.sh/pgw/doc/ing-pay-api).

## Error handling

Non-2xx responses are reported on the response object, whatever the gateway
sends:

```php
$response = $api->getTransaction($id);

if ($response->failed()) {
    $error = $response->error();   // code, message, errors, instance
}
```

By default only `422` and `500` raise `ApiErrorException`. Set
`services.imoje.strict_errors` to `true` and every non-2xx raises it instead,
with `getCode()` carrying the real HTTP status.

The gateway answers errors in two shapes — most codes wrap the detail in an
`apiErrorResponse` envelope, `400` and `401` arrive bare — and both are read the
same way. A `401` carries no message at all, so the exception falls back to the
reason phrase.

## Unknown enum values

A value the package does not know — a channel ING ships after your version of
it — hydrates to `null` rather than throwing, so one new payment method does not
break response parsing. Enum-typed properties on response DTOs are therefore
nullable.

Requests are unchanged: an unknown value passed into a request DTO still throws,
so a typo in your own code fails before it reaches the gateway.

That asymmetry is why two deprecations read differently. `Currency::UAH` and
`Currency::HRK` are deprecated because the gateway answers `422` for both and
no specification mentions either — they are request values, so nothing is lost
by leaving them unused.

`TransactionStatus::SUBMITTED_FOR_SETTLEMENT` and `TransactionStatus::REFUND`
appear in no specification either — chapter 04 lists seven statuses and makes
`refund` a transaction *type* — but they are **kept**. A status is a response
value nobody chooses, and under the rule above a case that is gone comes back
as `null`, so removing one the gateway does send would hide it rather than
report it. Neither could be observed: the sandbox never settles a payment.

`TransactionStatus::canChange()` is deprecated. Nothing calls it, no
specification describes a state machine, and its settled-to-refund transition
is not one the gateway makes.

## Falsy values

A `0`, `false`, `''` or `'0'` you set is sent, and one the gateway returns is
kept. Only `null` is dropped from a request, which matches the gateway: it
accepts an empty `title` but rejects a null one.

To create a payment link with no expiry, omit `validTo` rather than passing
`null` — the gateway reports `validTo: null` for such a link either way.

## Trusted IPs

`GET` and `PUT /settings/ips` answer with different shapes and share one DTO:
`getTrustedIps()` fills `trustedIps`, `putTrustedIps()` fills `status`, and the
other property is `null`. An empty allowlist reads as `[]`, not `null`, so it is
distinguishable from a response that carried no list at all.

## Payment profiles

`GET /profile/cid/{cid}` is a list endpoint: one customer id can hold several
cards. Use `getProfilesByCid()`, which hydrates every entry:

```php
foreach ($api->getProfilesByCid($cid)->paymentProfiles as $profile) {
    echo $profile->maskedNumber;
}
```

`getProfileByCid()` is deprecated. It returns the singular `ProfileResponseDto`,
whose `paymentProfile` is always `null` here, because the endpoint sends a list
and that DTO has nowhere to put one. It keeps its signature so existing code
compiles, but it cannot see the data.

`getProfile()` and `deactivateProfile()` take a profile id and stay singular.

An unknown `cid` answers `404` with an empty body. That is not an exception —
`paymentProfiles` is then `[]` and `failed()` is `true`, so check `failed()`
rather than reading an empty list as "this customer has no cards".

## Timeouts, and why there are no retries

The client carries Laravel's own defaults — 30 seconds for the request, 10 for
the connection — and both are now yours to set:

```php
// config/services.php
'imoje' => [
    // ...
    'timeout' => 8,
    'connect_timeout' => 3,
],
```

Fractions are allowed, so `0.5` is half a second. Leave either out and you get
the figure above, which is what the package already behaved like.

Nothing is retried, on purpose. Chapter 05 answers a `500` with "switch that
channel off for a few minutes", and a `503` or `504` with a **single** `GET`
five minutes later — not with another attempt. Repeating a `POST` the gateway
may already have accepted would risk a second transaction against the same
order, and no timeout tells you which side of that line you are on. Backing off
is the caller's decision to make, with the order context the package does not
have.

## Methods and channels

`PaymentMethod` is the method, `PaymentMethodCode` the channel inside it, and
both now hold exactly what the gateway accepts — twelve methods and
seventy-five channels, which is more than the published schema lists.

`visibleMethod` and `preselectMethodCode` take the enums as readily as strings:

```php
$api->createPayment(new PaymentDto([
    // ...
    'visibleMethod' => [PaymentMethod::BLIK, PaymentMethod::LEASE],
    'preselectMethodCode' => PaymentMethodCode::BLIK,
]));
```

One enum covers `paymentMethod` and `visibleMethod` both. The schema describes
them as two lists — `ing` in one, `lease` in the other — but the gateway
answers either field with the same twelve values, so there is nothing to split.

Two cases are deprecated rather than removed, each refused by the gateway on
the field its name promises: `PaymentMethod::VISA_MOBILE` is a channel, not a
method, and `PaymentMethodCode::IMOJE_INSTALLMENTS` is a method, not a channel.

## Multipayout

Pass `multipayout` as a flat list; the package puts it where each endpoint
expects it — under `data` for a transaction and a profile charge, at the top
level for a payment link.

```php
$api->createTransaction(new TransactionDto([
    // ...
    'multipayout' => [
        ['ban' => '72105000028166973380325415', 'amount' => 100, 'label' => 'Nazwa firmy 0'],
        ['ban' => '58105000025503268251444948', 'amount' => 200, 'label' => 'Nazwa firmy 1'],
    ],
]));
```

Entries are validated before the request leaves: `ban` must be 26 digits,
`label` at most 70 characters, `amount` at least 1, and `title` — optional —
becomes mandatory for every recipient once any one of them has it. A violation
raises `InvalidMultipayoutException`, which names the offending index and field.

Only `PLN` is supported, the feature has to be enabled for the merchant, and
under Split Payment the total is capped at the order's net amount. None of those
three is checkable locally, so the gateway reports them.

## Instalments

Instalments are `paymentMethod` `imoje_installments` with `paymentMethodCode`
`inbank` or `inbank_0`. The number of instalments goes under `installment`, not
as a top-level field, and is optional — omit it and the payer chooses on the
gateway.

```php
$api->createTransaction(new TransactionDto([
    // ...
    'paymentMethod' => PaymentMethod::IMOJE_INSTALLMENTS,
    'paymentMethodCode' => PaymentMethodCode::INBANK,
    'installment' => ['period' => 12],
]));
```

`period` is validated before the request leaves: 3 to 36 for `inbank`, 3 to 10
for `inbank_0`. A violation raises `InvalidInstallmentException` rather than a
round trip ending in `TRX-ERROR-120022`.

The order must be between 300 and 50 000 PLN. That is not checked locally; the
gateway answers `TRX-ERROR-120011` below the floor.

## Google Pay and Apple Pay

Both ride `createTransaction()` with `paymentMethod` `wallet` and
`paymentMethodCode` `gpay` or `applepay`. One `wallet` field carries either
provider — `paymentData` picks Apple Pay, its absence Google Pay:

```php
$api->createTransaction(new TransactionDto([
    // ...
    'paymentMethod' => PaymentMethod::WALLET,
    'paymentMethodCode' => PaymentMethodCode::GOOGLE_PAY,
    'wallet' => [
        'signature' => $token['signature'],
        'protocolVersion' => $token['protocolVersion'],
        'signedMessage' => $token['signedMessage'],
    ],
    'additionalData' => ['browser' => [
        'ip' => '127.0.0.1', 'language' => 'pl-PL', 'jsEnabled' => true,
        'timezoneOffset' => 100, 'userAgent' => $request->userAgent(),
        'accept' => 'application/json', 'javaEnabled' => false,
        'screenColorDepth' => 24, 'screenHeight' => 1080, 'screenWidth' => 2560,
    ]],
]));
```

Apple Pay takes the token's `paymentData` instead:

```php
'wallet' => ['paymentData' => [
    'version' => $token['version'],
    'data' => $token['data'],
    'signature' => $token['signature'],
    'header' => [
        'ephemeralPublicKey' => $token['header']['ephemeralPublicKey'],
        'publicKeyHash' => $token['header']['publicKeyHash'],
        'transactionId' => $token['header']['transactionId'],
    ],
]],
```

`additionalData.browser` is required for a wallet payment, and all ten of its
fields are required with it. The gateway types them strictly — `'true'` and
`'100'` are refused where `true` and `100` are wanted — so `BrowserDto` casts
them for you. Google Pay needs all three token fields; Apple Pay needs
`version`, `data`, `signature` and a `header`, whose `transactionId` must be at
least 64 characters. Violations raise `InvalidWalletException` naming the
field.

Each provider has to be enabled for the merchant. Until it is, the gateway
answers `406` with `TRX-ERROR-120003` rather than a validation error — so a
`406` here means the payload was fine and the service was not available.

A card issuer may still demand 3-D Secure. When the response carries an
`action` with a `url`, send the payer there; otherwise continue to your own
success page.

