<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidInvoiceException;
use Routegroup\Imoje\Payment\Factories\Casts\VatExemptionDtoFactory;
use Routegroup\Imoje\Payment\Types\VatExemptionType;

/**
 * @property-read VatExemptionType $type
 * @property-read string|null $text
 *
 * @method static VatExemptionDtoFactory factory($count = null, $state = [])
 */
class VatExemptionDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'type' => VatExemptionType::class,
    ];

    /** @throws InvalidInvoiceException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        if (($attributes['type'] ?? null) === null) {
            throw new InvalidInvoiceException('basisForVatExemption.type', 'is required');
        }

        parent::__construct($attributes);

        if ($this->type === VatExemptionType::OTHER && ($this->text ?? '') === '') {
            throw new InvalidInvoiceException('basisForVatExemption.text', 'is required when type is OTHER');
        }
    }

    protected static function newFactory(): VatExemptionDtoFactory
    {
        return VatExemptionDtoFactory::new();
    }
}
