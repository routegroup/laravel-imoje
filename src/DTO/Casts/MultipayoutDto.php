<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidMultipayoutException;
use Routegroup\Imoje\Payment\Factories\Casts\MultipayoutDtoFactory;

/**
 * One recipient of a split. Only PLN is supported, and under Split Payment the
 * total is capped at the order's net amount — neither is checkable here.
 *
 * @property-read string $ban
 * @property-read int $amount
 * @property-read string $label
 * @property-read string|null $title
 *
 * @method static MultipayoutDtoFactory factory($count = null, $state = [])
 */
class MultipayoutDto extends BaseDto
{
    use HasFactory;

    private const BAN = '/^[0-9]{26}$/';

    private const LABEL = '/^[A-Za-z0-9\-,. \x{00C0}-\x{02C0}"\']+$/u';

    private const TITLE = '/^[A-Za-z0-9#&_\-,.\\\\\/ \x{00C0}-\x{02C0}"\']+$/u';

    protected bool $allowNull = true;

    protected array $casts = [
        'amount' => 'int',
    ];

    /**
     * Supplying a title for one recipient makes it mandatory for the rest, so
     * the list is validated as a whole rather than entry by entry.
     *
     * @param  list<array<string, mixed>|MultipayoutDto>  $entries
     * @return list<MultipayoutDto>
     *
     * @throws InvalidMultipayoutException
     */
    public static function fromList(array $entries): array
    {
        $list = [];

        foreach (array_values($entries) as $index => $entry) {
            $list[] = $entry instanceof self ? $entry : new self((array) $entry, $index);
        }

        $titled = array_filter($list, static fn (self $dto): bool => $dto->title !== null);

        if ($titled !== [] && count($titled) !== count($list)) {
            foreach ($list as $index => $dto) {
                if ($dto->title === null) {
                    throw new InvalidMultipayoutException($index, 'title', 'is required once any recipient has one');
                }
            }
        }

        return $list;
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidMultipayoutException
     */
    public function __construct($attributes = [], int $index = 0)
    {
        $attributes = (array) $attributes;

        $this->assert($index, 'ban', $attributes['ban'] ?? null, self::BAN, 26);
        $this->assert($index, 'label', $attributes['label'] ?? null, self::LABEL, 70);

        if (($attributes['title'] ?? null) !== null) {
            $this->assert($index, 'title', $attributes['title'], self::TITLE, 105);
        }

        $amount = $attributes['amount'] ?? null;

        if (! is_numeric($amount) || (int) $amount < 1) {
            throw new InvalidMultipayoutException($index, 'amount', 'must be an integer of at least 1');
        }

        parent::__construct($attributes);
    }

    /** @throws InvalidMultipayoutException */
    private function assert(int $index, string $field, mixed $value, string $pattern, int $max): void
    {
        if (! is_string($value) || $value === '') {
            throw new InvalidMultipayoutException($index, $field, 'is required');
        }

        if (mb_strlen($value) > $max) {
            throw new InvalidMultipayoutException($index, $field, "must be at most $max characters");
        }

        if (preg_match($pattern, $value) !== 1) {
            throw new InvalidMultipayoutException($index, $field, 'contains characters the gateway rejects');
        }
    }

    protected static function newFactory(): MultipayoutDtoFactory
    {
        return MultipayoutDtoFactory::new();
    }
}
