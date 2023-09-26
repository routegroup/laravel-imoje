<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Requests\ChargeProfileRequestDto;
use Routegroup\Imoje\Payment\DTO\Requests\RefundRequestDto;
use Routegroup\Imoje\Payment\DTO\Responses\ChargeProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\RefundResponseDto;

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
    ): RefundResponseDto {
        $url = $this->url->createRefundUrl($transactionId);
        $response = $this->request()->post($url, $dto);

        return new RefundResponseDto($response);
    }

    public function getProfile(
        string $profileId
    ): ProfileResponseDto {
        $url = $this->url->createProfileIdUrl($profileId);
        $response = $this->request()->get($url);

        return new ProfileResponseDto($response);
    }

    public function chargeProfile(
        ChargeProfileRequestDto $dto
    ): ChargeProfileResponseDto {
        $url = $this->url->createChargeProfileUrl();
        $response = $this->request()->post($url, $dto);

        return new ChargeProfileResponseDto($response);
    }

    public function deactivateProfile(
        string $profileId
    ): ProfileResponseDto {
        $url = $this->url->createProfileIdUrl($profileId);
        $response = $this->request()->delete($url);

        return new ProfileResponseDto($response);
    }

    protected function request(): PendingRequest
    {
        return Http::asJson()
            ->acceptJson()
            ->withToken($this->config->apiKey);
    }
}
