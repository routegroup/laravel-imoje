<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Responses\ServicesResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ServicesResponseDtoFactory extends Factory
{
    protected $model = ServicesResponseDto::class;

    public function definition(): array
    {
        return [];
    }
}
