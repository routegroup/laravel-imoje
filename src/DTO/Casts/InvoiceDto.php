<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidInvoiceException;
use Routegroup\Imoje\Payment\Factories\Casts\InvoiceDtoFactory;

/**
 * Split Payment carries invoiceId and a taxAmount per position; ING Księgowość
 * carries neither and may add basisForVatExemption and issueInvoice. invoiceId
 * picks the variant. It sits beside buyer, not inside it, whatever the
 * chapter's table says.
 *
 * @property-read string|null $invoiceId
 * @property-read VatExemptionDto|null $basisForVatExemption
 * @property-read bool|null $issueInvoice
 * @property-read InvoiceBuyerDto $buyer
 * @property-read list<InvoicePositionDto> $positions
 *
 * @method static InvoiceDtoFactory factory($count = null, $state = [])
 */
class InvoiceDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'buyer' => InvoiceBuyerDto::class,
        'basisForVatExemption' => VatExemptionDto::class,
        'issueInvoice' => 'bool',
    ];

    /** @throws InvalidInvoiceException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        if (! isset($attributes['buyer'])) {
            throw new InvalidInvoiceException('buyer', 'is required');
        }

        $positions = $attributes['positions'] ?? null;

        if (! is_array($positions)) {
            throw new InvalidInvoiceException('positions', 'is required');
        }

        $attributes['positions'] = InvoicePositionDto::fromList(
            $positions,
            ($attributes['invoiceId'] ?? null) !== null,
        );

        parent::__construct($attributes);
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();

        $attributes['positions'] = array_map(
            static fn (mixed $p): mixed => $p instanceof InvoicePositionDto ? $p->toArray() : $p,
            is_array($attributes['positions'] ?? null) ? $attributes['positions'] : [],
        );

        return $attributes;
    }

    /** The paywall carries the invoice as one signed field, gzipped at level 5. */
    public function encoded(): string
    {
        return base64_encode((string) gzencode((string) json_encode($this->toArray()), 5));
    }

    protected static function newFactory(): InvoiceDtoFactory
    {
        return InvoiceDtoFactory::new();
    }
}
