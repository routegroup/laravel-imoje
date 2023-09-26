<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Services;

use Illuminate\Http\Request;
use Routegroup\Imoje\Payment\DTO\Responses\CancelledResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ChargeProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\OneClickResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ResponseDto;
use Routegroup\Imoje\Payment\Exceptions\NotImplementedException;
use Routegroup\Imoje\Payment\Exceptions\SchemaValidationException;
use Routegroup\Imoje\Payment\Lib\Validator;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\TransactionSource;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use Routegroup\Imoje\Payment\Types\TransactionType;

class NotificationService
{
    public function __construct(
        public readonly Validator $validator
    ) {
    }

    /**
     * @throws NotImplementedException
     * @throws SchemaValidationException
     */
    public function resolve(Request $request): ResponseDto
    {
        $data = $request->toArray();

        $this->validator->fromNotification($data);

        if (static::isOneClick($data)) {
            return OneClickResponseDto::make($data);
        }

        if (static::isCancelled($data)) {
            return CancelledResponseDto::make($data);
        }

        if (static::isChargeProfile($data)) {
            return ChargeProfileResponseDto::make($data);
        }

        throw new NotImplementedException($data);
    }

    public function isOneClick(array $attributes): bool
    {
        return array_key_exists('payment', $attributes)
            && array_key_exists('transaction', $attributes)
            && $attributes['transaction']['paymentMethod'] === PaymentMethod::CARD->value
            && $attributes['transaction']['source'] === TransactionSource::WEB->value;
    }

    public function isCancelled(array $attributes): bool
    {
        return array_key_exists('payment', $attributes)
            && $attributes['payment']['status'] === TransactionStatus::CANCELLED->value;
    }

    public function isChargeProfile(array $attributes): bool
    {
        return array_key_exists('transaction', $attributes)
            && $attributes['transaction']['paymentMethod'] === PaymentMethod::CARD->value
            && $attributes['transaction']['source'] === 'api'
            && $attributes['transaction']['type'] === TransactionType::SALE->value;
    }
}
