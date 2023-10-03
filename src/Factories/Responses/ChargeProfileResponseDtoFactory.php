<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Responses\ChargeProfileResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ChargeProfileResponseDtoFactory extends Factory
{
    protected $model = ChargeProfileResponseDto::class;

    public function definition(): array
    {
        return [
            'transaction' => TransactionDto::factory()->withPaymentProfile(),
        ];
    }
}
