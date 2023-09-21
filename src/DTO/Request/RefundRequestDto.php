<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Request;

use JetBrains\PhpStorm\ArrayShape;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Lib\Config;

/**
 * @property-read string $type
 * @property-read string $serviceId
 * @property-read int $amount
 * @property-read string $title
 */
class RefundRequestDto extends BaseDto
{
    protected array $casts = [
        'amount' => 'int',
    ];

    public static function make(
        #[ArrayShape([
            // Required
            'type' => 'string',
            'serviceId' => 'string',
            'amount' => 'int',
            // Optional
            'title' => 'string',
        ])] array $attributes
    ): BaseDto {
        $config = app(Config::class);

        $attributes = array_merge_recursive([
            'type' => 'refund',
            'serviceId' => $config->serviceId,
        ], $attributes);

        return parent::make($attributes);
    }
}
