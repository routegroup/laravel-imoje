<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Notifications;

use Illuminate\Database\Eloquent\Model;
use Routegroup\Imoje\Payment\DTO\Casts\ActionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionPaymentDto;
use Routegroup\Imoje\Payment\DTO\Notifications\GenericNotificationDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use Routegroup\Imoje\Payment\Types\TransactionType;

class GenericNotificationDtoFactory extends Factory
{
    protected $model = GenericNotificationDto::class;

    public function definition(): array
    {
        return [
            'transaction' => TransactionDto::factory()->withPaymentProfile()->state([
                'type' => TransactionType::SALE,
                'status' => TransactionStatus::SETTLED,
            ]),
            'payment' => TransactionPaymentDto::factory()->state([
                'status' => TransactionStatus::SETTLED,
            ]),
        ];
    }

    public function make($attributes = [], ?Model $parent = null): GenericNotificationDto
    {
        $dto = parent::make($attributes, $parent);

        $orderId = $attributes['orderId'] ?? $this->faker->unique()->uuid;
        $transactionId = $attributes['transactionId'] ?? $this->faker->unique()->uuid;

        $data = $dto->toArray();
        $data['transaction']['id'] = $transactionId;
        $data['transaction']['title'] = $orderId;
        $data['transaction']['orderId'] = $orderId;
        $data['payment']['id'] = $transactionId;
        $data['payment']['orderId'] = $orderId;

        return new GenericNotificationDto($data);
    }

    public function asPending(): static
    {
        return $this->state([
            'transaction' => TransactionDto::factory()->state([
                'type' => TransactionType::SALE,
                'status' => TransactionStatus::PENDING,
            ]),
            'payment' => TransactionPaymentDto::factory()->state([
                'status' => TransactionStatus::PENDING,
            ]),
            'action' => ActionDto::factory(),
        ]);
    }
}
