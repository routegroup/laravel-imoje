<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Api;

use Routegroup\Imoje\Payment\DTO\Api\PutTrustedIpsDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class PutTrustedIpsDtoFactory extends Factory
{
    protected $model = PutTrustedIpsDto::class;

    public function definition(): array
    {
        return [
            'trustedIps' => $this->faker->randomElements(
                array_map(fn (): string => $this->faker->unique()->ipv4, range(1, 6)),
                $this->faker->numberBetween(1, 4),
            ),
        ];
    }
}
