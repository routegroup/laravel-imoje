<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Requests\ChargeProfileRequestDto;
use Routegroup\Imoje\Payment\DTO\Requests\RefundRequestDto;
use Routegroup\Imoje\Payment\DTO\Responses\ChargeProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\RefundResponseDto;
use Routegroup\Imoje\Payment\Exceptions\ApiErrorException;

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
        $this->validateResponse($response, $dto->toArray());

        return new RefundResponseDto($response);
    }

    public function getProfile(
        string $profileId
    ): ProfileResponseDto {
        $url = $this->url->createProfileIdUrl($profileId);
        $response = $this->request()->get($url);
        $this->validateResponse($response, compact('profileId'));

        return new ProfileResponseDto($response);
    }

    public function chargeProfile(
        ChargeProfileRequestDto $dto
    ): ChargeProfileResponseDto {
        $url = $this->url->createChargeProfileUrl();
        $response = $this->request()->post($url, $dto);
        $this->validateResponse($response, $dto->toArray());

        return new ChargeProfileResponseDto($response);
    }

    public function deactivateProfile(
        string $profileId
    ): ProfileResponseDto {
        $url = $this->url->createProfileIdUrl($profileId);
        $response = $this->request()->delete($url);
        $this->validateResponse($response, compact('profileId'));

        return new ProfileResponseDto($response);
    }

    protected function request(): PendingRequest
    {
        return Http::asJson()
            ->acceptJson()
            ->withToken($this->config->apiKey);
    }

    /**
     * @throws ApiErrorException
     */
    protected function validateResponse(
        Response $response,
        array $request = [],
    ): void {
        if ($response->ok()) {
            return;
        }

        match ($response->status()) {
            422 => throw new ApiErrorException($response, $request),
            500 => throw new ApiErrorException($response)
        };
    }
}
