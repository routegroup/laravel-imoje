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
            'trustedIps' => ['127.0.0.1'],
        ];
    }
}
