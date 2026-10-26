<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Http\Client\Response;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\ApiErrorDto;

/**
 * Most codes wrap the detail in an `apiErrorResponse` envelope; 400 and 401
 * arrive bare. Both are normalised here.
 *
 * @property-read string|null $code
 * @property-read string|null $message
 * @property-read mixed $instance
 * @property-read list<ApiErrorDto>|array<array-key, mixed> $errors Hydrated when the
 *     gateway sends its usual list; passed through untouched otherwise
 */
class ApiErrorResponseDto extends BaseDto
{
    public static function fromResponse(Response $response): self
    {
        $body = $response->collect()->toArray();
        $envelope = $body['apiErrorResponse'] ?? null;

        return new self(is_array($envelope) ? $envelope : $body);
    }

    /**
     * @param  array<array-key, mixed>  $attributes
     */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        /** BaseDto has no 'array' cast, so `errors` has to be normalised rather than cast. */
        $errors = $attributes['errors'] ?? null;
        $attributes['errors'] = is_array($errors)
            ? array_map(
                // The gateway sends a list of {property, message}; anything else
                // it has never sent rides through as it arrived.
                static fn (mixed $error): mixed => is_array($error) ? new ApiErrorDto($error) : $error,
                $errors,
            )
            : [];

        parent::__construct($attributes);
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();

        $attributes['errors'] = array_map(
            static fn (mixed $error): mixed => $error instanceof ApiErrorDto ? $error->toArray() : $error,
            $this->errors,
        );

        return $attributes;
    }
}
