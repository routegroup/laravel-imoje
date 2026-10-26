<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Concerns;

use Routegroup\Imoje\Payment\DTO\Casts\MultipayoutDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidMultipayoutException;

trait HasMultipayout
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     *
     * @throws InvalidMultipayoutException
     */
    protected static function castMultipayout(array $attributes): array
    {
        if (isset($attributes['multipayout']) && is_array($attributes['multipayout'])) {
            $attributes['multipayout'] = MultipayoutDto::fromList($attributes['multipayout']);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function flattenMultipayout(array $attributes): array
    {
        if (isset($attributes['multipayout']) && is_array($attributes['multipayout'])) {
            $attributes['multipayout'] = array_map(
                static fn (mixed $entry): mixed => $entry instanceof MultipayoutDto ? $entry->toArray() : $entry,
                $attributes['multipayout'],
            );
        }

        return $attributes;
    }
}
