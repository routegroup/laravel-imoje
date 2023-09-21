<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Request\ChargeProfileRequestDto;
use Routegroup\Imoje\Payment\DTO\Request\RefundRequestDto;

class Api
{
    public function __construct(
        public readonly Utils $utils,
    ) {
    }

    public function createRefund(
        RefundRequestDto $dto,
        string $transactionId
    ): Response {
        $url = $this->utils->createRefundUrl($transactionId);

        return $this
            ->request()
            ->post($url, $dto);
    }

    public function chargeProfile(
        ChargeProfileRequestDto $dto
    ): Response {
        $url = $this->utils->createChargeProfileUrl();

        return $this
            ->request()
            ->post($url, $dto);
    }

    public function deactivateProfile(
        string $profileId
    ): Response {
        $url = $this->utils->createDeactivateProfileUrl($profileId);

        return $this
            ->request()
            ->delete($url);
    }

    protected function request(): PendingRequest
    {
        return Http::asJson()
            ->acceptJson()
            ->withToken($this->utils->apiKey);
    }
}
