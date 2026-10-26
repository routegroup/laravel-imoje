<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\ApplePayHeaderDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ApplePayHeaderDtoFactory extends Factory
{
    protected $model = ApplePayHeaderDto::class;

    public function definition(): array
    {
        return [
            'ephemeralPublicKey' => 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE',
            'publicKeyHash' => 'xrJqUFO2jN5edGS8iETkfNXtwqnwSqRUUrQJBysFpZ8=',
            'transactionId' => str_repeat('a', ApplePayHeaderDto::TRANSACTION_ID_LENGTH),
        ];
    }
}
