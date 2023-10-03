<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Responses\PaymentResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class PaymentResponseDtoFactory extends Factory
{
    protected $model = PaymentResponseDto::class;

    public function definition(): array
    {
        return [
            'payment' => PaymentDto::factory(),
        ];
    }
}
