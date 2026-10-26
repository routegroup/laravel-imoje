<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidCartException;
use Routegroup\Imoje\Payment\Factories\Casts\CartItemDtoFactory;
use Routegroup\Imoje\Payment\Types\CartItemState;
use Routegroup\Imoje\Payment\Types\LeaseTaxStake;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read int $amount Net, in the currency's smallest unit
 * @property-read int|float $tax
 * @property-read LeaseTaxStake $taxStake
 * @property-read int $quantity
 * @property-read string $url
 * @property-read string $categoryId
 * @property-read string|null $unit
 * @property-read CartItemState|null $state
 * @property-read CartDiscountDto|null $discount
 *
 * @method static CartItemDtoFactory factory($count = null, $state = [])
 */
class CartItemDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'amount' => 'int',
        'quantity' => 'int',
        'state' => CartItemState::class,
        'discount' => CartDiscountDto::class,
    ];

    /**
     * @param  list<array<string, mixed>|CartItemDto>  $items
     * @return list<CartItemDto>
     *
     * @throws InvalidCartException
     */
    public static function fromList(array $items): array
    {
        if ($items === []) {
            throw new InvalidCartException('items', 'must hold at least one item');
        }

        return array_map(
            static fn (mixed $i): self => $i instanceof self ? $i : new self((array) $i),
            array_values($items),
        );
    }

    /** @throws InvalidCartException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        if (isset($attributes['taxStake']) && ! $attributes['taxStake'] instanceof LeaseTaxStake) {
            $stake = LeaseTaxStake::fromRate($attributes['taxStake']);

            if ($stake === null) {
                throw new InvalidCartException('items.taxStake', 'is not a rate Lease Now accepts');
            }

            $attributes['taxStake'] = $stake;
        }

        foreach (['id', 'name', 'amount', 'tax', 'taxStake', 'quantity', 'url', 'categoryId'] as $field) {
            if (($attributes[$field] ?? null) === null || $attributes[$field] === '') {
                throw new InvalidCartException("items.$field", 'is required');
            }
        }

        if (! is_numeric($attributes['quantity']) || $attributes['quantity'] < 1) {
            throw new InvalidCartException('items.quantity', 'must be at least 1');
        }

        if (filter_var($attributes['url'], FILTER_VALIDATE_URL) === false) {
            throw new InvalidCartException('items.url', 'must be a valid URL');
        }

        parent::__construct($attributes);
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();

        // transformValues renders a backed enum as its value, which would quote
        // the percentage the gateway insists on receiving as a number.
        $attributes['taxStake'] = $this->taxStake->encoded();

        return $attributes;
    }

    protected static function newFactory(): CartItemDtoFactory
    {
        return CartItemDtoFactory::new();
    }
}
