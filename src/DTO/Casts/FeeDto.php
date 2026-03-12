<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Routegroup\Imoje\Payment\DTO\BaseDto;

/**
 * @property-read int|null $merchant
 * @property-read int|null $surcharge
 */
class FeeDto extends BaseDto
{
    protected bool $allowNull = true;

    protected array $casts = [
        'merchant' => 'int',
        'surcharge' => 'int',
    ];
}
