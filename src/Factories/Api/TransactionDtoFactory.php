<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Api;

use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\BillingDto;
use Routegroup\Imoje\Payment\DTO\Casts\CardDto;
use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

class TransactionDtoFactory extends Factory
{
    protected $model = TransactionDto::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(1, 100000) * 100,
            'currency' => Currency::PLN,
            'orderId' => $this->faker->unique()->uuid,
            'paymentMethod' => PaymentMethod::PAY_BY_LINK,
            'paymentMethodCode' => PaymentMethodCode::IPKO,
            'successReturnUrl' => 'https://imoje.requestcatcher.com',
            'failureReturnUrl' => 'https://imoje.requestcatcher.com',
            'customer' => CustomerDto::factory(),
            'billing' => BillingDto::factory(),
            'shipping' => BillingDto::factory(),
        ];
    }

    public function asCard(): static
    {
        return $this->state([
            'paymentMethod' => PaymentMethod::CARD,
            'paymentMethodCode' => PaymentMethodCode::ONECLICK,
            'card' => CardDto::factory(),
            'additionalData' => [
                'browser' => [
                    'ip' => '127.0.0.1',
                    'language' => 'pl-PL',
                    'jsEnabled' => true,
                    'timezoneOffset' => 100,
                    'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36',
                    'accept' => 'application/json, text/javascript, */*; q=0.01',
                    'javaEnabled' => false,
                    'screenColorDepth' => 24,
                    'screenHeight' => 1080,
                    'screenWidth' => 2560,
                ],
            ],
        ]);
    }
}
