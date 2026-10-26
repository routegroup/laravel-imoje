# API

For more details, please follow the provider's docs.

- [PL](https://imojeapi.docs.apiary.io/)
- [EN](https://imojeapieng.docs.apiary.io/)

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
