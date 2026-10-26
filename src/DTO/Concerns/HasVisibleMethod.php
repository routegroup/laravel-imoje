<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Concerns;

use BackedEnum;

trait HasVisibleMethod
{
    /**
     * transformValues only unwraps an enum at the top level, and visibleMethod
     * is a list, so its entries are flattened here instead.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected static function castVisibleMethod(array $attributes): array
    {
        if (isset($attributes['visibleMethod']) && is_array($attributes['visibleMethod'])) {
            $attributes['visibleMethod'] = array_map(
                static fn (mixed $method): mixed => $method instanceof BackedEnum ? $method->value : $method,
                $attributes['visibleMethod'],
            );
        }

        return $attributes;
    }
}
