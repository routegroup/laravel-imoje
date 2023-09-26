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
}
