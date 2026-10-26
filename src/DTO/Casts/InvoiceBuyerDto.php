<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidInvoiceException;
use Routegroup\Imoje\Payment\Factories\Casts\InvoiceBuyerDtoFactory;
use Routegroup\Imoje\Payment\Types\InvoiceBuyerType;
use Routegroup\Imoje\Payment\Types\InvoiceIdType;

/**
 * @property-read InvoiceBuyerType $type
 * @property-read string $email
 * @property-read string $fullName
 * @property-read string $street
 * @property-read string $city
 * @property-read string $postalCode
 * @property-read string $countryCodeAlpha2
 * @property-read string|null $idCountryCodeAlpha2
 * @property-read InvoiceIdType|null $idType
 * @property-read string|null $idNumber
 *
 * @method static InvoiceBuyerDtoFactory factory($count = null, $state = [])
 */
class InvoiceBuyerDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'type' => InvoiceBuyerType::class,
        'idType' => InvoiceIdType::class,
    ];

    /** @throws InvalidInvoiceException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        foreach (['type', 'email', 'fullName', 'street', 'city', 'postalCode', 'countryCodeAlpha2'] as $field) {
            if (($attributes[$field] ?? null) === null || $attributes[$field] === '') {
                throw new InvalidInvoiceException("buyer.$field", 'is required');
            }
        }

        parent::__construct($attributes);

        if ($this->type === InvoiceBuyerType::COMPANY && ($this->idType === null || $this->idNumber === null)) {
            throw new InvalidInvoiceException('buyer.idType', 'idType and idNumber are required for a COMPANY');
        }

        if ($this->idType === InvoiceIdType::VAT_ID && $this->idCountryCodeAlpha2 === null) {
            throw new InvalidInvoiceException('buyer.idCountryCodeAlpha2', 'is required when idType is VAT_ID');
        }
    }

    protected static function newFactory(): InvoiceBuyerDtoFactory
    {
        return InvoiceBuyerDtoFactory::new();
    }
}
