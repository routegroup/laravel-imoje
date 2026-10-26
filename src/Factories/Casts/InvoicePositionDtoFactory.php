<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\InvoicePositionDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\TaxStake;

class InvoicePositionDtoFactory extends Factory
{
    protected $model = InvoicePositionDto::class;

    public function definition(): array
    {
        $stake = $this->faker->randomElement(TaxStake::cases());
        $grossAmount = $this->faker->numberBetween(1, 2000) * 100;
        $rate = (int) (preg_match('/^TAX_(\d+)$/', $stake->value, $m) ? $m[1] : 0);

        return [
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->slug(2),
            'quantity' => $this->faker->numberBetween(1, 20),
            'unit' => $this->faker->randomElement(['Sztuki', 'Kilogramy', 'Godziny', 'Metry']),
            'taxStake' => $stake,
            'grossAmount' => $grossAmount,
            // The tax contained in a gross amount, not added on top of it.
            'taxAmount' => (int) round($grossAmount * $rate / (100 + $rate)),
        ];
    }
}
