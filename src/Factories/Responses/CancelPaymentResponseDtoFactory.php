<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\DTO\Responses\CancelPaymentResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\TransactionStatus;

class CancelPaymentResponseDtoFactory extends Factory
{
    protected $model = CancelPaymentResponseDto::class;

    public function definition(): array
    {
        $now = now();

        return [
            'id' => $this->faker->unique()->uuid,
            'url' => "https://sandbox.paywall.imoje.pl/pay/{$this->faker->unique()->uuid}",
            'serviceId' => config('services.imoje.service_id'),
            'orderId' => $this->faker->unique()->uuid,
            'title' => '',
            'simp' => '',
            'amount' => $this->faker->numberBetween(1, 1000) * 100,
            'currency' => Currency::PLN,
            'status' => TransactionStatus::CANCELLED,
            'isActive' => true,
            'validTo' => null,
            'created' => $now->timestamp,
            'modified' => $now->timestamp,
            'isGenerated' => false,
            'isUsed' => false,
            'usedAt' => null,
            'isConfirmVisited' => false,
            'confirmVisitedAt' => null,
            'returnUrl' => 'https://imoje.requestcatcher.com',
            'failureReturnUrl' => 'https://imoje.requestcatcher.com',
            'successReturnUrl' => 'https://imoje.requestcatcher.com',
            'customer' => CustomerDto::factory(),
        ];
    }
}
