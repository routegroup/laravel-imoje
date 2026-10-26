<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\ActionDtoFactory;
use Routegroup\Imoje\Payment\Types\ActionType;

/**
 * @property-read ActionType|null $type
 * @property-read string|null $url
 * @property-read string|null $method
 * @property-read string|null $contentType
 * @property-read string|null $contentBodyRaw
 * @property-read string|null $ban
 * @property-read string|null $title
 *
 * @method static ActionDtoFactory factory($count = null, $state = [])
 */
class ActionDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'type' => ActionType::class,
        'url' => 'string',
        'method' => 'string',
        'contentType' => 'string',
        'contentBodyRaw' => 'string',
        'ban' => 'string',
    ];

    protected static function newFactory(): ActionDtoFactory
    {
        return ActionDtoFactory::new();
    }
}
