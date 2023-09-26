<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Notifications\OneClickNotificationDto;
use Routegroup\Imoje\Payment\Exceptions\NotImplementedException;
use Routegroup\Imoje\Payment\Exceptions\SchemaValidationException;
use Routegroup\Imoje\Payment\Lib\Validator;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;
use Routegroup\Imoje\Payment\Types\TransactionSource;
use Routegroup\Imoje\Payment\Types\TransactionType;

class NotificationService
{
    public function __construct(
        public readonly Validator $validator
    ) {
    }

    /**
     * @throws SchemaValidationException
     * @throws NotImplementedException
     */
    public function resolve(Request $request): BaseDto
    {
        $data = $request->toArray();

        $this->validator->fromNotification($data);

        if ($this->isOneClick($data)) {
            return new OneClickNotificationDto($data);
        }

        throw new NotImplementedException($data);
    }

    protected function isOneClick(array $data): bool
    {
        return $this->isEqualWithStructure($data, [
            'transaction' => [
                'type' => TransactionType::SALE->value,
                'source' => TransactionSource::WEB->value,
                'paymentMethod' => PaymentMethod::CARD->value,
                'paymentMethodCode' => PaymentMethodCode::ONECLICK->value,
            ],
        ]);
    }

    protected function isEqualWithStructure(array $data, array $structure): bool
    {
        $structure = Arr::dot($structure);
        $data = Arr::dot($data);

        return count($structure) === count(array_intersect($structure, $data));
    }
}
