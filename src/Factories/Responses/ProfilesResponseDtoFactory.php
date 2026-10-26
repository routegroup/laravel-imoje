<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\PaymentProfileDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfilesResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ProfilesResponseDtoFactory extends Factory
{
    protected $model = ProfilesResponseDto::class;

    /** The shape the specification declares for GET /profile/cid/{cid}. */
    public function definition(): array
    {
        return [
            'paymentProfiles' => [
                PaymentProfileDto::factory()->make()->toArray(),
            ],
        ];
    }
}
