<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Responses\ServicesResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ServicesResponseDtoFactory extends Factory
{
    protected $model = ServicesResponseDto::class;

    /** The gateway answers with a top-level list of {service} objects. */
    public function definition(): array
    {
        return [
            [
                'service' => [
                    'id' => $this->faker->uuid,
                    'created' => $this->faker->unixTime,
                    'isActive' => true,
                    'name' => $this->faker->company,
                    'paymentMethods' => [
                        ['paymentMethod' => 'pbl', 'paymentMethodCode' => 'ipko'],
                    ],
                ],
            ],
        ];
    }
}
