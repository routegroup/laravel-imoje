<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidInvoiceException;
use Routegroup\Imoje\Payment\Factories\Casts\InvoicePositionDtoFactory;
use Routegroup\Imoje\Payment\Types\TaxStake;

/**
 * @property-read string $name
 * @property-read string $code
 * @property-read int|float $quantity
 * @property-read string $unit
 * @property-read int $grossAmount
 * @property-read TaxStake $taxStake
 * @property-read int|null $taxAmount
 * @property-read int|null $discountAmount
 *
 * @method static InvoicePositionDtoFactory factory($count = null, $state = [])
 */
class InvoicePositionDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'grossAmount' => 'int',
        'taxAmount' => 'int',
    ];

    /**
     * @param  list<array<string, mixed>|InvoicePositionDto>  $positions
     * @return list<InvoicePositionDto>
     *
     * @throws InvalidInvoiceException
     */
    public static function fromList(array $positions, bool $requireTaxAmount = false): array
    {
        if ($positions === []) {
            throw new InvalidInvoiceException('positions', 'must hold at least one position');
        }

        return array_map(
            static fn (mixed $p): self => $p instanceof self ? $p : new self((array) $p, $requireTaxAmount),
            array_values($positions),
        );
    }

    /** @throws InvalidInvoiceException */
    public function __construct($attributes = [], bool $requireTaxAmount = false)
    {
        $attributes = (array) $attributes;

        if (isset($attributes['taxStake']) && ! $attributes['taxStake'] instanceof TaxStake) {
            $stake = TaxStake::fromPercentage($attributes['taxStake']);

            if ($stake === null) {
                throw new InvalidInvoiceException('positions.taxStake', 'is not a rate the gateway accepts');
            }

            $attributes['taxStake'] = $stake;
        }

        $required = ['name', 'code', 'unit', 'taxStake', 'grossAmount'];

        if ($requireTaxAmount) {
            $required[] = 'taxAmount';
        }

        foreach ($required as $field) {
            if (($attributes[$field] ?? null) === null || $attributes[$field] === '') {
                throw new InvalidInvoiceException("positions.$field", 'is required');
            }
        }

        $quantity = $attributes['quantity'] ?? null;

        if (! is_numeric($quantity) || $quantity < 0) {
            throw new InvalidInvoiceException('positions.quantity', 'is required and cannot be negative');
        }

        parent::__construct($attributes);
    }

    protected static function newFactory(): InvoicePositionDtoFactory
    {
        return InvoicePositionDtoFactory::new();
    }
}
