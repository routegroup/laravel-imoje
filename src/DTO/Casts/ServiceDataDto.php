<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\ServiceDataDtoFactory;

/**
 * @property-read string $id
 * @property-read int $created
 * @property-read bool $isActive
 * @property-read list<ServicePaymentMethodDto> $paymentMethods
 *
 * @method static ServiceDataDtoFactory factory($count = null, $state = [])
 */
class ServiceDataDto extends BaseDto
{
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;
        $methods = $attributes['paymentMethods'] ?? [];

        $attributes['paymentMethods'] = array_map(
            static fn (mixed $method): mixed => is_array($method)
                ? new ServicePaymentMethodDto($method)
                : $method,
            is_array($methods) ? $methods : [],
        );

        parent::__construct($attributes);
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();

        $attributes['paymentMethods'] = array_map(
            static fn (mixed $method): mixed => $method instanceof ServicePaymentMethodDto
                ? $method->toArray()
                : $method,
            is_array($attributes['paymentMethods'] ?? null) ? $attributes['paymentMethods'] : [],
        );

        return $attributes;
    }

    use HasFactory;

    protected array $casts = [
        'created' => 'int',
        'isActive' => 'bool',
    ];

    protected static function newFactory(): ServiceDataDtoFactory
    {
        return ServiceDataDtoFactory::new();
    }
}
