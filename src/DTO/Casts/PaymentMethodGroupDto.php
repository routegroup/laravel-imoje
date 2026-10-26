<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\PaymentMethodGroupDtoFactory;

/**
 * get-payment-methods groups its answer by method and lists the channels
 * inside each, which is a different shape from the flat list a service reports.
 *
 * @property-read string|null $label
 * @property-read bool|null $isOnline
 * @property-read int|null $priority
 * @property-read array|null $image
 * @property-read list<PaymentMethodChannelDto> $channels
 *
 * @method static PaymentMethodGroupDtoFactory factory($count = null, $state = [])
 */
class PaymentMethodGroupDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'isOnline' => 'bool',
        'priority' => 'int',
    ];

    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;
        $channels = $attributes['channels'] ?? [];

        $attributes['channels'] = array_map(
            static fn (mixed $channel): mixed => is_array($channel)
                ? new PaymentMethodChannelDto($channel)
                : $channel,
            is_array($channels) ? $channels : [],
        );

        parent::__construct($attributes);
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();

        $attributes['channels'] = array_map(
            static fn (mixed $channel): mixed => $channel instanceof PaymentMethodChannelDto
                ? $channel->toArray()
                : $channel,
            is_array($attributes['channels'] ?? null) ? $attributes['channels'] : [],
        );

        return $attributes;
    }

    protected static function newFactory(): PaymentMethodGroupDtoFactory
    {
        return PaymentMethodGroupDtoFactory::new();
    }
}
