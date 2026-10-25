# Notifications

This package provides base verification for signature, schema structure and mappings for responses.

```php
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Routegroup\Imoje\Payment\DTO\Notifications\GenericNotificationDto;
use Routegroup\Imoje\Payment\Lib\Validator;

public function postNotification(Request $request, Validator $validator): JsonResponse 
{
    // Can throw InvalidSignatureException
    $validator->verifySignature($request);
    
    // Can throw SchemaValidationException
    $validator->fromNotification($request->toArray());
    
    /**
     * There should be custom logic which will determine 
     * what kind of response is based on needs.
     */
    $notification = new GenericNotificationDto($request->toArray());
    
    return response()->json(['status' => 'ok']);
}
```

`verifySignature()` throws `InvalidSignatureException` when the body is empty, the
`x-imoje-signature` header is missing or names an unknown algorithm, the digest does
not match, or the header's `merchantid` / `serviceid` differ from the configured ones.

Answer a notification you accepted with `200 OK` and `{"status":"ok"}`. On anything
else ING retries 3× every 10s, then 5× every 5min, 5× hourly, 5× every 6h and 5× every
12h. Two identical notifications may arrive; handle the first and ignore the duplicate.

## Report notifications

Notifications announcing that a scheduled report is ready go to the notification
address configured for that report, not to the shop's address, and **carry no
signature**. Do not pass them to `verifySignature()` — it rejects unsigned payloads by
design, and accepting one would mean trusting an attacker-supplied download url. Route
them to their own endpoint and map them with `ReportNotificationDto`:

```php
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Routegroup\Imoje\Payment\DTO\Notifications\ReportNotificationDto;

public function postReportNotification(Request $request): JsonResponse
{
    $report = new ReportNotificationDto($request->toArray());

    // $report->status is 'generated'; $report->fileExt is csv, owi1 or mt940.
    // $report->url is the download address.

    return response()->json(['status' => 'ok']);
}
```
