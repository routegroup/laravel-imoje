<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Response;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\NotImplementedException;
use Routegroup\Imoje\Payment\Exceptions\ValidationException;
use Routegroup\Imoje\Payment\Lib\Validate;

class NotificationDto extends BaseDto
{
    /**
     * @throws NotImplementedException
     * @throws ValidationException
     */
    public static function make(array $attributes): BaseDto
    {
        Validate::notification($attributes);

        if (static::isOneClick($attributes)) {
            return OneClickResponseDto::make($attributes);
        }

        if (static::isCancelled($attributes)) {
            return CancelledResponseDto::make($attributes);
        }

        if (static::isChargeProfile($attributes)) {
            return ChargeProfileResponseDto::make($attributes);
        }

        throw new NotImplementedException();
    }

    public static function isOneClick(array $attributes): bool
    {
        return array_key_exists('payment', $attributes)
            && array_key_exists('transaction', $attributes)
            && $attributes['transaction']['paymentMethod'] === 'card'
            && $attributes['transaction']['source'] === 'web';
    }

    public static function isCancelled(array $attributes): bool
    {
        return array_key_exists('payment', $attributes)
            && $attributes['payment']['status'] === 'cancelled';
    }

    public static function isChargeProfile(array $attributes): bool
    {
        return array_key_exists('transaction', $attributes)
            && $attributes['transaction']['paymentMethod'] === 'card'
            && $attributes['transaction']['source'] === 'api';
    }
}
