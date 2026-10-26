<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\AdditionalDataDto;
use Routegroup\Imoje\Payment\DTO\Casts\BrowserDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class AdditionalDataDtoFactory extends Factory
{
    protected $model = AdditionalDataDto::class;

    public function definition(): array
    {
        return [
            'browser' => BrowserDto::factory()->make()->toArray(),
        ];
    }
}
