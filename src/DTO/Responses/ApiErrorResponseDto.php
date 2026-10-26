<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Http\Client\Response;
use Routegroup\Imoje\Payment\DTO\BaseDto;

/**
 * Most codes wrap the detail in an `apiErrorResponse` envelope; 400 and 401
 * arrive bare. Both are normalised here.
 *
 * @property-read string|null $code
 * @property-read string|null $message
 * @property-read mixed $instance
 * @property-read array $errors
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
        $attributes['errors'] = is_array($errors) ? $errors : [];

        parent::__construct($attributes);
    }
}
