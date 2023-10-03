# Notifications

This package provides base verification for signature, schema structure and mappings for responses.

```php
use Routegroup\Imoje\Payment\DTO\Notifications\Paywall\TransactionNotificationDto;
use Routegroup\Imoje\Payment\Lib\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
    $notification = new TransactionNotificationDto($request->toArray());
    
    return response()->json(['status' => 'ok']);
}
```
