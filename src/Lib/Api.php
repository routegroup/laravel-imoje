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
        public readonly Config $config,
        public readonly Url $url,
    ) {
    }

    public function createRefund(
        RefundRequestDto $dto,
        string $transactionId
    ): Response {
        $url = $this->url->createRefundUrl($transactionId);

        return $this
            ->request()
            ->post($url, $dto);
    }

    public function getProfile(
        string $profileId
    ): Response {
        return $this->request()->get();
    }

    public function chargeProfile(
        ChargeProfileRequestDto $dto
    ): Response {
        $url = $this->url->createChargeProfileUrl();

        return $this
            ->request()
            ->post($url, $dto);
    }

    public function deactivateProfile(
        string $profileId
    ): Response {
        $url = $this->url->createProfileIdUrl($profileId);

        return $this
            ->request()
            ->delete($url);
    }

    protected function request(): PendingRequest
    {
        return Http::asJson()
            ->acceptJson()
            ->withToken($this->config->apiKey);
    }
}
