<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Illuminate\Support\Str;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentProfileDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class PaymentProfileDtoFactory extends Factory
{
    protected $model = PaymentProfileDto::class;

    public function definition(): array
    {
        $expirationDate = $this->faker->creditCardExpirationDate;

        return [
            'id' => $this->faker->unique()->uuid,
            'merchantMid' => Str::random(),
            'merchantCustomerId' => $this->faker->unique()->numberBetween(1, 999999),
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'maskedNumber' => $this->faker->numerify('****####'),
            'month' => $expirationDate->format('m'),
            'year' => $expirationDate->format('Y'),
            'organization' => $this->faker->creditCardType,
            'isActive' => true,
            'profile' => 'ONE_CLICK',
        ];
    }
}
