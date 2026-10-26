<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Http\Client\Response;
use Routegroup\Imoje\Payment\DTO\BaseDto;

abstract class ResponseDto extends BaseDto
{
    public readonly Response $response;

    public function __construct(Response $incomingData)
    {
        $this->response = $incomingData;
        $attributes = $incomingData->collect()->toArray();
        parent::__construct($attributes);
    }

    public function failed(): bool
    {
        return ! $this->response->successful();
    }

    public function error(): ?ApiErrorResponseDto
    {
        return $this->failed()
            ? ApiErrorResponseDto::fromResponse($this->response)
            : null;
    }
}
