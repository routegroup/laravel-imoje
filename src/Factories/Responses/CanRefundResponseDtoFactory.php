<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Responses\CanRefundResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class CanRefundResponseDtoFactory extends Factory
{
    protected $model = CanRefundResponseDto::class;

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(1, 10000) * 100;

        return [
            'id' => $this->faker->unique()->uuid,
            'refundable' => true,
            'balance' => true,
            'fullRefund' => $amount,
            'partialRefund' => [
                'maxRefundAmount' => $amount,
                'minRefundAmount' => 1,
            ],
        ];
    }
}
