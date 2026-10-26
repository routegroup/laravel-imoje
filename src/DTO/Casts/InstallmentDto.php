<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidInstallmentException;
use Routegroup\Imoje\Payment\Factories\Casts\InstallmentDtoFactory;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

/**
 * @property-read int $period
 *
 * @method static InstallmentDtoFactory factory($count = null, $state = [])
 */
class InstallmentDto extends BaseDto
{
    use HasFactory;

    public const MIN_PERIOD = 3;

    /** inbank allows up to 36 instalments, inbank_0 up to 10. */
    public const MAX_PERIOD = [
        PaymentMethodCode::INBANK->value => 36,
        PaymentMethodCode::INBANK_0->value => 10,
    ];

    protected array $casts = [
        'period' => 'int',
    ];

    /**
     * @param  array{period?: int|string}  $attributes
     *
     * @throws InvalidInstallmentException
     */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;
        $period = $attributes['period'] ?? null;

        if (! is_numeric($period) || (int) $period != $period) {
            throw new InvalidInstallmentException($period, 'is required and must be a whole number');
        }

        $period = (int) $period;
        $ceiling = max(self::MAX_PERIOD);

        if ($period < self::MIN_PERIOD || $period > $ceiling) {
            throw new InvalidInstallmentException($period, 'must be between '.self::MIN_PERIOD." and $ceiling");
        }

        parent::__construct($attributes);
    }

    /** @throws InvalidInstallmentException */
    public function assertFitsChannel(PaymentMethodCode $code): void
    {
        $max = self::MAX_PERIOD[$code->value] ?? null;

        if ($max !== null && $this->period > $max) {
            throw new InvalidInstallmentException(
                $this->period,
                "must be at most $max for {$code->value}",
            );
        }
    }

    protected static function newFactory(): InstallmentDtoFactory
    {
        return InstallmentDtoFactory::new();
    }
}
