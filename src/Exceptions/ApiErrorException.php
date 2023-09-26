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
        $data = $response->collect()->toArray();

        if (array_key_exists('apiErrorResponse', $data)) {
            $data = $data['apiErrorResponse'];
        }

        $this->response = $response;
        $this->dto = new ApiErrorResponseDto($data);
        $this->request = $request;

        parent::__construct($this->dto->message, 422);
    }

    public function context(): array
    {
        return [
            'response' => $this->dto->toArray(),
            'request' => $this->request,
        ];
    }
}
