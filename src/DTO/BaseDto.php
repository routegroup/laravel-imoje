<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO;

use Illuminate\Support\Fluent;
use Routegroup\Imoje\Payment\Lib\Utils;

/**
 * @template TKey of array-key
 * @template TValue
 */
abstract class BaseDto extends Fluent
{
    /**
     * @var array<string, string>
     */
    protected array $casts = [];

    public function __construct($attributes = [])
    {
        $attributes = $this->castAttributes($attributes);
        parent::__construct($attributes);
    }

    /**
     * @param  array<TKey, TValue>  $attributes
     * @return array<TKey, TValue> $attributes
     */
    private function castAttributes(array $attributes): array
    {
        foreach ($this->casts as $attributeKey => $cast) {
            $attributes[$attributeKey] = $this->castAttribute($cast, $attributes[$attributeKey] ?? null);
        }

        return $attributes;
    }

    public function castAttribute(string $castType, mixed $value): mixed
    {
        if (is_a($castType, BaseDto::class, true)) {
            return $castType::make($value ?? []);
        }

        if (enum_exists($castType)) {
            return $value instanceof $castType
                ? $value
                : $castType::from($value);
        }

        return match ($castType) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            default => $value,
        };
    }

    /**
     * @return array<TKey, TValue> $attributes
     */
    public function toArray(): array
    {
        return app(Utils::class)->transformValues($this->attributes);
    }

    /**
     * @param  array<TKey, TValue>  $attributes
     */
    public static function make(array $attributes): BaseDto
    {
        /* @phpstan-ignore-next-line */
        return new static($attributes);
    }
}
