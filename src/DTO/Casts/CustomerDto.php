<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\CustomerDtoFactory;
use Routegroup\Imoje\Payment\Types\Lang;

/**
 * @property-read string $firstName
 * @property-read string $lastName
 * @property-read string $email
 * @property-read string|null $cid
 * @property-read string|null $company
 * @property-read string|null $phone
 * @property-read Lang $locale
 */
class CustomerDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'firstName' => 'string',
        'lastName' => 'string',
        'email' => 'email',
        'locale' => Lang::class,
    ];

    protected static function newFactory(): CustomerDtoFactory
    {
        return CustomerDtoFactory::new();
    }
}
