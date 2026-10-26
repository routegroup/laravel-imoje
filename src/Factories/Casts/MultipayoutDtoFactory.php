<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\MultipayoutDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class MultipayoutDtoFactory extends Factory
{
    protected $model = MultipayoutDto::class;

    public function definition(): array
    {
        return [
            'ban' => $this->faker->numerify(str_repeat('#', 26)),
            'amount' => $this->faker->numberBetween(1, 1000) * 100,
            'label' => 'Nazwa firmy '.$this->faker->numberBetween(0, 99),
        ];
    }

    public function withTitle(): static
    {
        return $this->state(fn (): array => [
            'title' => 'Platnosc '.$this->faker->numerify('######'),
        ]);
    }
}
