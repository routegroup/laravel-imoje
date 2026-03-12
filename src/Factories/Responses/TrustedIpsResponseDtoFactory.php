<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Responses\TrustedIpsResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class TrustedIpsResponseDtoFactory extends Factory
{
    protected $model = TrustedIpsResponseDto::class;

    public function definition(): array
    {
        return [
            'trustedIps' => ['127.0.0.1'],
        ];
    }
}
