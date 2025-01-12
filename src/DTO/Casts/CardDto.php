<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\CardDtoFactory;

/**
 * @property-read string $firstName
 * @property-read string $lastName
 * @property-read string $number
 * @property-read string $month
 * @property-read string $year
 * @property-read string $cvv
 *
 * @method static CardDtoFactory factory($count = null, $state = [])
 */
class CardDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'firstName' => 'string',
        'lastName' => 'string',
        'number' => 'string',
        'month' => 'string',
        'year' => 'string',
        'cvv' => 'string',
    ];

    protected static function newFactory(): CardDtoFactory
    {
        return CardDtoFactory::new();
    }
}
