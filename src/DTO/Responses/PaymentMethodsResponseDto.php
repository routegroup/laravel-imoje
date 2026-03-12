<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\Factories\Responses\PaymentMethodsResponseDtoFactory;

/**
 * @property-read array $methods
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
        $this->attributes = [
            'methods' => is_array($raw) ? $raw : [],
        ];
    }

    protected static function newFactory(): PaymentMethodsResponseDtoFactory
    {
        return PaymentMethodsResponseDtoFactory::new();
    }
}
