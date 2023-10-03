<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Responses\RefundResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class RefundResponseDtoFactory extends Factory
{
    protected $model = RefundResponseDto::class;

    public function definition(): array
    {
        return [
            'transaction' => TransactionDto::factory()->asApiRefund(),
        ];
    }
}
