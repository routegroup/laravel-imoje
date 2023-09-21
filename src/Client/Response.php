<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Client;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Client\Response as OriginalResponse;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidDtoNameException;

class Response implements Arrayable, ArrayAccess
{
    public function __construct(
        public readonly OriginalResponse $originalResponse,
        public readonly BaseDto $data
    ) {
    }

    public static function resolve(
        OriginalResponse $clientResponse,
        string $dto,
    ): Response {
        if (! is_subclass_of($dto, BaseDto::class)) {
            throw new InvalidDtoNameException();
        }

        return new static(
            $clientResponse,
            new $dto($clientResponse->collect()->toArray())
        );
    }

    public function toArray(): array
    {
        return $this->data->toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        //
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    public function __get($key)
    {
        return $this->data->get($key);
    }

    public function __isset(mixed $key)
    {
        return $this->offsetExists($key);
    }

    public function __unset(mixed $key)
    {
        $this->offsetUnset($key);
    }
}
