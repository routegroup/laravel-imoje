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
 * @property-read array $paymentMethods
 *
 * @method static ServiceDataDtoFactory factory($count = null, $state = [])
 */
class ServiceDataDto extends BaseDto
{
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
