<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use JetBrains\PhpStorm\ArrayShape;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Api\CancelPaymentDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;

/**
 * @property-read string $serviceId
 * @property-read string $paymentId
 */
class CancelPaymentDto extends BaseDto
{
    use HasFactory;

    public function __construct(
        #[ArrayShape([
            // Required
            'paymentId' => 'string',
            // Required but passed by default
            'serviceId' => 'string',
        ])] array $attributes = []
    ) {
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
