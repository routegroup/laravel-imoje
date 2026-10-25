<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\Factories\Responses\PaymentMethodsResponseDtoFactory;

/**
 * @property-read array $methods Payment methods, unwrapped from the gateway's envelope
 * @property-read int|null $timestamp Present only when the gateway sends the envelope
 *
 * @method static PaymentMethodsResponseDtoFactory factory($count = null, $state = [])
 */
class PaymentMethodsResponseDto extends ResponseDto
{
    use HasFactory;

    public function __construct($incomingData)
    {
        parent::__construct($incomingData);

        $raw = $this->attributes;
        $data = $raw['data'] ?? null;

        // The specification declares a bare list of methods. The gateway sends
        // that same list wrapped in {timestamp, data}. Accept either, so the
        // documented shape keeps working if ING ever drops the envelope.
        $methods = match (true) {
            array_is_list($raw) => $raw,
            is_array($data) && array_is_list($data) => $data,
            default => [],
        };

        $this->attributes = [
            'methods' => $methods,
            'timestamp' => $raw['timestamp'] ?? null,
        ];
    }

    protected static function newFactory(): PaymentMethodsResponseDtoFactory
    {
        return PaymentMethodsResponseDtoFactory::new();
    }
}
