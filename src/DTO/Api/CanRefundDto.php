<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use JetBrains\PhpStorm\ArrayShape;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Api\CanRefundDtoFactory;

/**
 * @property-read int $amount
 *
 * @method static CanRefundDtoFactory factory($count = null, $state = [])
 */
class CanRefundDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'amount' => 'int',
    ];

    public function __construct(
        #[ArrayShape([
            // Required
            'amount' => 'int',
        ])] array $attributes = []
    ) {
        parent::__construct($attributes);
    }

    protected static function newFactory(): CanRefundDtoFactory
    {
        return CanRefundDtoFactory::new();
    }
}
