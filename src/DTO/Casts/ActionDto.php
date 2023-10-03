<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\ActionDtoFactory;
use Routegroup\Imoje\Payment\Types\ActionType;

/**
 * @property-read ActionType $type
 * @property-read string $url
 * @property-read string $method
 * @property-read string $contentType
 * @property-read string $contentBodyRaw
 * @property-read string|null $ban
 */
class ActionDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'type' => ActionType::class,
        'url' => 'string',
        'method' => 'string',
        'contentType' => 'string',
        'contentBodyRaw' => 'string',
    ];

    protected static function newFactory(): ActionDtoFactory
    {
        return ActionDtoFactory::new();
    }
}
