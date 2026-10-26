<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidCartException;
use Routegroup\Imoje\Payment\Factories\Casts\CartDiscountDtoFactory;

/**
 * @property-read int $amount
 * @property-read int|float $tax
 *
 * @method static CartDiscountDtoFactory factory($count = null, $state = [])
 */
class CartDiscountDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'amount' => 'int',
    ];

    /** @throws InvalidCartException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        // The gateway takes the pair or nothing; either half alone is refused.
        foreach (['amount', 'tax'] as $field) {
            if (($attributes[$field] ?? null) === null) {
                throw new InvalidCartException("items.discount.$field", 'is required once a discount is given');
            }
        }

        parent::__construct($attributes);
    }

    protected static function newFactory(): CartDiscountDtoFactory
    {
        return CartDiscountDtoFactory::new();
    }
}
