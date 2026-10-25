<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Notifications;

use Routegroup\Imoje\Payment\DTO\Notifications\ReportNotificationDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ReportNotificationDtoFactory extends Factory
{
    protected $model = ReportNotificationDto::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'status' => 'generated',
            'fileExt' => $this->faker->randomElement(['csv', 'owi1', 'mt940']),
            'created' => $this->faker->unixTime,
            'modified' => $this->faker->unixTime,
            'url' => $this->faker->url,
        ];
    }
}
