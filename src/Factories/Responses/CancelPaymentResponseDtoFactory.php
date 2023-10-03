<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Responses\CancelPaymentResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class CancelPaymentResponseDtoFactory extends Factory
{
    protected $model = CancelPaymentResponseDto::class;

    public function definition(): array
    {
        return [];
    }
}
