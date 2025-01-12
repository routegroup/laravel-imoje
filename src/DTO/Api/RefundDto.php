<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use JetBrains\PhpStorm\ArrayShape;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Api\RefundDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\TransactionType;

/**
 * @property-read int $amount
 * @property-read TransactionType $type
 * @property-read string $serviceId
 * @property-read string $title
 *
 * @method static RefundDtoFactory factory($count = null, $state = [])
 */
class RefundDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'amount' => 'int',
        'type' => TransactionType::class,
    ];

    public function __construct(
        #[ArrayShape([
            // Required
            'amount' => 'int',
            // Required but passed by default
            'type' => 'string',
            'serviceId' => 'string',
            // Optional
            'title' => 'string',
        ])] array $attributes = []
    ) {
        $config = app(Config::class);

        $attributes = array_merge_recursive([
            'type' => TransactionType::REFUND,
            'serviceId' => $config->serviceId,
        ], $attributes);

        parent::__construct($attributes);
    }

    protected static function newFactory(): RefundDtoFactory
    {
        return RefundDtoFactory::new();
    }
}
