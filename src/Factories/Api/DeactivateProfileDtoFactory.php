<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Api;

use Routegroup\Imoje\Payment\DTO\Api\DeactivateProfileDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class DeactivateProfileDtoFactory extends Factory
{
    protected $model = DeactivateProfileDto::class;

    public function definition(): array
    {
        return [
            'paymentProfileId' => $this->faker->unique()->uuid,
        ];
    }
}
