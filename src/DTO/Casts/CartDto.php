<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidCartException;
use Routegroup\Imoje\Payment\Factories\Casts\CartDtoFactory;

/**
 * @property-read list<CartItemDto> $items
 *
 * @method static CartDtoFactory factory($count = null, $state = [])
 */
class CartDto extends BaseDto
{
    use HasFactory;

    /** @throws InvalidCartException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;
        $items = $attributes['items'] ?? null;

        if (! is_array($items)) {
            throw new InvalidCartException('items', 'is required');
        }

        parent::__construct(['items' => CartItemDto::fromList($items)]);
    }

    public function toArray(): array
    {
        return ['items' => array_map(
            static fn (CartItemDto $item): array => $item->toArray(),
            $this->items,
        )];
    }

    /** The paywall carries the cart as one signed field, gzipped at level 5. */
    public function encoded(): string
    {
        return base64_encode((string) gzencode((string) json_encode($this->toArray()), 5));
    }

    protected static function newFactory(): CartDtoFactory
    {
        return CartDtoFactory::new();
    }
}
