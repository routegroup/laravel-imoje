<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO;

use Illuminate\Support\Fluent;
use Routegroup\Imoje\Payment\Exceptions\ReadOnlyDtoException;
use Routegroup\Imoje\Payment\Lib\Utils;

abstract class BaseDto extends Fluent
{
    protected array $casts = [];

    protected bool $allowNull = false;

    public function __construct($attributes = [])
    {
        $attributes = $this->castAttributes($attributes);
        parent::__construct($attributes);
    }

    private function castAttributes(array $attributes): array
    {
        foreach ($this->casts as $attributeKey => $cast) {
            $attributes[$attributeKey] = $this->castAttribute($cast, $attributes[$attributeKey] ?? null);
        }

        if ($this->allowNull) {
            $attributes = array_filter($attributes);
        }

        return $attributes;
    }

    public function castAttribute(string $castType, mixed $value): mixed
    {
        if ($this->allowNull && empty($value)) {
            return null;
        }

        if (is_a($castType, BaseDto::class, true)) {
            return $value instanceof BaseDto
                ? $value
                : new $castType($value ?? []);
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

    public function toArray(): array
    {
        unset($this->attributes['getKey']);

        return app(Utils::class)->transformValues($this->attributes);
    }

    /** @throws ReadOnlyDtoException */
    public function offsetSet($offset, $value): void
    {
        throw new ReadOnlyDtoException();
    }
}
