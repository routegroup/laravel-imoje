# Notifications

This package provides base verification for signature, schema structure and mappings for responses.

```php
use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;use Routegroup\Imoje\Payment\DTO\Notifications\GenericNotificationDto;use Routegroup\Imoje\Payment\Lib\Validator;

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
