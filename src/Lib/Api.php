<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\Client\Response;
use Routegroup\Imoje\Payment\DTO\Request\ChargeProfileRequestDto;
use Routegroup\Imoje\Payment\DTO\Request\RefundRequestDto;
use Routegroup\Imoje\Payment\DTO\Response\ChargeProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Response\ProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Response\RefundResponseDto;

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
    ) {
        $url = $this->url->createRefundUrl($transactionId);

        return Response::resolve(
            $this->request()->post($url, $dto),
            RefundResponseDto::class
        );
    }

    public function getProfile(
        string $profileId
    ): Response {
        $url = $this->url->createProfileIdUrl($profileId);

        return Response::resolve(
            $this->request()->get($url),
            ProfileResponseDto::class
        );
    }

    public function chargeProfile(
        ChargeProfileRequestDto $dto
    ): Response {
        $url = $this->url->createChargeProfileUrl();

        return Response::resolve(
            $this->request()->post($url, $dto),
            ChargeProfileResponseDto::class
        );
    }

    public function deactivateProfile(
        string $profileId
    ): Response {
        $url = $this->url->createProfileIdUrl($profileId);

        return Response::resolve(
            $this->request()->delete($url),
            ProfileResponseDto::class
        );
    }

    protected function request(): PendingRequest
    {
        return Http::asJson()
            ->acceptJson()
            ->withToken($this->config->apiKey);
    }
}
