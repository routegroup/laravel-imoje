<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\ActionDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\ActionType;

class ActionDtoFactory extends Factory
{
    protected $model = ActionDto::class;

    public function definition(): array
    {
        return [
            'type' => ActionType::REDIRECT,
            'url' => '',
            'method' => 'GET',
            'contentType' => '',
            'contentBodyRaw' => '',
        ];
    }

    public function wireTransfer(): static
    {
        return $this->state([
            'iban' => $this->faker->iban,
        ]);
    }
}
