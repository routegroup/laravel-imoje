<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Concerns\ValidatesGatewayFields;
use Routegroup\Imoje\Payment\Exceptions\InvalidFieldException;
use Routegroup\Imoje\Payment\Factories\Casts\CustomerDtoFactory;
use Routegroup\Imoje\Payment\Types\Lang;

/**
 * @phpstan-type CustomerShape array{
 *     firstName?: string,
 *     lastName?: string,
 *     email?: string,
 *     cid?: string,
 *     company?: string,
 *     phone?: string,
 *     locale?: Lang|string,
 * }
 *
 * @property-read string $firstName
 * @property-read string $lastName
 * @property-read string|null $email
 * @property-read string|null $cid
 * @property-read string|null $company
 * @property-read string|null $phone
 * @property-read Lang|null $locale
 *
 * @method static CustomerDtoFactory factory($count = null, $state = [])
 */
class CustomerDto extends BaseDto
{
    use HasFactory;
    use ValidatesGatewayFields;

    protected bool $allowNull = true;

    protected array $casts = [
        'firstName' => 'string',
        'lastName' => 'string',
        'email' => 'string',
        'locale' => Lang::class,
    ];

    /** @throws InvalidFieldException */
    public function __construct($attributes = [])
    {
        self::checkGatewayFields((array) $attributes, [
            'firstName' => 'name',
            'lastName' => 'name',
            'company' => 'description',
            'email' => 'email',
            'phone' => 'phone',
            'cid' => 'customerId',
        ]);

        parent::__construct($attributes);
    }

    protected static function newFactory(): CustomerDtoFactory
    {
        return CustomerDtoFactory::new();
    }
}
