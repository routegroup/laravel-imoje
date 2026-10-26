<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;
use Routegroup\Imoje\Payment\DTO\Responses\ApiErrorResponseDto;

class ApiErrorException extends Exception
{
    public readonly Response $response;

    public readonly ApiErrorResponseDto $dto;

    public readonly array $request;

    public function __construct(
        Response $response,
        array $request = [],
    ) {
        $this->response = $response;
        $this->dto = ApiErrorResponseDto::fromResponse($response);
        $this->request = $request;

        parent::__construct(
            $this->dto->message ?: ($response->reason() ?: 'HTTP '.$response->status()),
            $response->status(),
        );
    }

    public function status(): int
    {
        return $this->response->status();
    }

    public function context(): array
    {
        return [
            'status' => $this->status(),
            'response' => $this->dto->toArray(),
            'request' => $this->request,
        ];
    }
}
