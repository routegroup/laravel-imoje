<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Api\CancelPaymentDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;

/**
 * @property-read string $serviceId
 * @property-read string $paymentId
 *
 * @method static CancelPaymentDtoFactory factory($count = null, $state = [])
 */
class CancelPaymentDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'serviceId' => 'string',
        'paymentId' => 'string',
    ];

    /**
     * @param array{
     *     // Required
     *     paymentId?: string,
     *     // Required but passed by default
     *     serviceId?: string,
     * } $attributes
     */
    public function __construct(array $attributes = [])
    {
        $config = app(Config::class);

        $attributes = array_merge_recursive([
            'serviceId' => $config->serviceId,
        ], $attributes);

        parent::__construct($attributes);
    }

    protected static function newFactory(): CancelPaymentDtoFactory
    {
        return CancelPaymentDtoFactory::new();
    }
}
