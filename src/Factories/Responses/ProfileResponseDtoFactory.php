<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\PaymentProfileDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfileResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ProfileResponseDtoFactory extends Factory
{
    protected $model = ProfileResponseDto::class;

    public function definition(): array
    {
        return [
            'paymentProfile' => PaymentProfileDto::factory(),
        ];
    }
}
