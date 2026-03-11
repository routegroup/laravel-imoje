<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\ServiceDataDto;
use Routegroup\Imoje\Payment\DTO\Responses\ServiceResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ServiceResponseDtoFactory extends Factory
{
    protected $model = ServiceResponseDto::class;

    public function definition(): array
    {
        return [
            'service' => ServiceDataDto::factory()->make()->toArray(),
        ];
    }
}
