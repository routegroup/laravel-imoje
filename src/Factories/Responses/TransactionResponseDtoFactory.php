<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\ActionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Responses\TransactionResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class TransactionResponseDtoFactory extends Factory
{
    protected $model = TransactionResponseDto::class;

    public function definition(): array
    {
        return [
            'transaction' => TransactionDto::factory()->asApiPending(),
            'action' => ActionDto::factory(),
        ];
    }
}
