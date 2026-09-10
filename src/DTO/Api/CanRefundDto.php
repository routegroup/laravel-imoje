<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    /**
     * @param array{
     *     // Required
     *     amount?: int,
     * } $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected static function newFactory(): CanRefundDtoFactory
    {
        return CanRefundDtoFactory::new();
    }
}
