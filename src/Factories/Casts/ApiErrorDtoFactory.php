<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\ApiErrorDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ApiErrorDtoFactory extends Factory
{
    protected $model = ApiErrorDto::class;

    public function definition(): array
    {
        $field = $this->faker->word;

        return [
            'property' => 'instance.'.$field,
            'message' => $this->faker->randomElement([
                'requires property "'.$field.'"',
                'is not of a type(s) string',
                'does not meet minimum length of 1',
            ]),
        ];
    }
}
