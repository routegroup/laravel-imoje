<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\ApplePayHeaderDto;
use Routegroup\Imoje\Payment\DTO\Casts\ApplePayPaymentDataDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ApplePayPaymentDataDtoFactory extends Factory
{
    protected $model = ApplePayPaymentDataDto::class;

    public function definition(): array
    {
        return [
            'version' => 'EC_v1',
            'data' => 'DjI8Ez7nPhVo742aGEzABz/TfxCPt9LSdf2/h+0xIgVJbi3fQqd4lv3ogoTI3SQ7',
            'signature' => 'MIAGCSqGSIb3DQEHAqCAMIACAQExDzANBglghkgBZQMEAgEFADCABgkqhkiG9w0B',
            'header' => ApplePayHeaderDto::factory()->make()->toArray(),
        ];
    }
}
