<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Api\CancelPaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\CanRefundDto;
use Routegroup\Imoje\Payment\DTO\Api\ChargeProfileDto;
use Routegroup\Imoje\Payment\DTO\Api\DeactivateProfileDto;
use Routegroup\Imoje\Payment\DTO\Api\GetPaymentMethodsDto;
use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\PutTrustedIpsDto;
use Routegroup\Imoje\Payment\DTO\Api\RefundDto;
use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Responses\CancelPaymentResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\CanRefundResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ChargeProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\GetPaymentResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\GetTransactionResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\PaymentMethodsResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\PaymentResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfilesResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\RefundResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ServiceResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ServicesResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\TransactionResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\TrustedIpsResponseDto;
use Routegroup\Imoje\Payment\Exceptions\ApiErrorException;

class Api
{
    public function __construct(
        protected readonly Config $config,
        public readonly Url $url,
    ) {}

    public function createTransaction(
        TransactionDto $dto
    ): TransactionResponseDto {
        $url = $this->url->createTransactionUrl();
        $response = $this->request()->post($url, $dto->toArray());
        $this->validateResponse($response, $dto->toArray());

        return new TransactionResponseDto($response);
    }

    public function getTransaction(
        string $transactionId
    ): GetTransactionResponseDto {
        $url = $this->url->createGetTransactionUrl($transactionId);
        $response = $this->request()->get($url);
        $this->validateResponse($response, compact('transactionId'));

        return new GetTransactionResponseDto($response);
    }

    public function createPayment(
        PaymentDto $dto
    ): PaymentResponseDto {
        $url = $this->url->createPaymentUrl();
        $response = $this->request()->post($url, $dto->toArray());
        $this->validateResponse($response, $dto->toArray());

        return new PaymentResponseDto($response);
    }

    public function getPayment(
        string $paymentId
    ): GetPaymentResponseDto {
        $url = $this->url->createGetPaymentUrl($paymentId);
        $response = $this->request()->get($url);
        $this->validateResponse($response, compact('paymentId'));

        return new GetPaymentResponseDto($response);
    }

    public function cancelPayment(
        CancelPaymentDto $dto
    ): CancelPaymentResponseDto {
        $url = $this->url->createCancelPaymentUrl();
        $response = $this->request()->post($url, $dto->toArray());
        $this->validateResponse($response, $dto->toArray());

        return new CancelPaymentResponseDto($response);
    }

    public function createRefund(
        RefundDto $dto,
        string $transactionId
    ): RefundResponseDto {
        $url = $this->url->createRefundUrl($transactionId);
        $response = $this->request()->post($url, $dto->toArray());
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
        ChargeProfileDto $dto
    ): ChargeProfileResponseDto {
        $url = $this->url->createChargeProfileUrl();
        $response = $this->request()->post($url, $dto->toArray());
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

    public function captureTransaction(string $transactionId): TransactionResponseDto
    {
        $url = $this->url->createCaptureUrl($transactionId);
        $response = $this->request()->post($url);
        $this->validateResponse($response, compact('transactionId'));

        return new TransactionResponseDto($response);
    }

    public function voidTransaction(string $transactionId): TransactionResponseDto
    {
        $url = $this->url->createVoidUrl($transactionId);
        $response = $this->request()->post($url);
        $this->validateResponse($response, compact('transactionId'));

        return new TransactionResponseDto($response);
    }

    /**
     * @deprecated The endpoint answers with a list, which this DTO drops. Use getProfilesByCid().
     */
    public function getProfileByCid(string $cid): ProfileResponseDto
    {
        $url = $this->url->createProfileCidUrl($cid);
        $response = $this->request()->get($url);
        $this->validateResponse($response, compact('cid'));

        return new ProfileResponseDto($response);
    }

    public function getProfilesByCid(string $cid): ProfilesResponseDto
    {
        $url = $this->url->createProfileCidUrl($cid);
        $response = $this->request()->get($url);
        $this->validateResponse($response, compact('cid'));

        return new ProfilesResponseDto($response);
    }

    public function deactivateProfileByPost(DeactivateProfileDto $dto): ProfileResponseDto
    {
        $url = $this->url->createProfileDeactivateUrl();
        $response = $this->request()->post($url, $dto->toArray());
        $this->validateResponse($response, $dto->toArray());

        return new ProfileResponseDto($response);
    }

    public function canRefund(
        CanRefundDto $dto,
        string $transactionId
    ): CanRefundResponseDto {
        $url = $this->url->createCanRefundUrl($transactionId);
        $response = $this->request()->post($url, $dto->toArray());
        $this->validateResponse($response, $dto->toArray());

        return new CanRefundResponseDto($response);
    }

    public function getPaymentMethods(
        string $serviceId,
        GetPaymentMethodsDto $dto
    ): PaymentMethodsResponseDto {
        $url = $this->url->createGetPaymentMethodsUrl($serviceId);
        $response = $this->request()->post($url, $dto->toArray());
        $this->validateResponse($response, $dto->toArray());

        return new PaymentMethodsResponseDto($response);
    }

    public function getService(string $serviceId): ServiceResponseDto
    {
        $url = $this->url->createServiceUrl($serviceId);
        $response = $this->request()->get($url);
        $this->validateResponse($response, compact('serviceId'));

        return new ServiceResponseDto($response);
    }

    public function getServices(): ServicesResponseDto
    {
        $url = $this->url->createServicesUrl();
        $response = $this->request()->get($url);
        $this->validateResponse($response);

        return new ServicesResponseDto($response);
    }

    public function getTrustedIps(): TrustedIpsResponseDto
    {
        $url = $this->url->createSettingsIpsUrl();
        $response = $this->request()->get($url);
        $this->validateResponse($response);

        return new TrustedIpsResponseDto($response);
    }

    public function putTrustedIps(PutTrustedIpsDto $dto): TrustedIpsResponseDto
    {
        $url = $this->url->createSettingsIpsUrl();
        $response = $this->request()->put($url, $dto->toArray());
        $this->validateResponse($response, $dto->toArray());

        return new TrustedIpsResponseDto($response);
    }

    /**
     * Deliberately without retries. Chapter 05 answers a 500 with "disable the
     * channel for a few minutes" and a 503 or 504 with a single GET five
     * minutes later, not with another attempt — and repeating a POST the
     * gateway may already have accepted risks a second transaction.
     */
    protected function request(): PendingRequest
    {
        return Http::asJson()
            ->acceptJson()
            ->timeout($this->config->timeout)
            ->connectTimeout($this->config->connectTimeout)
            ->withToken($this->config->apiKey);
    }

    /**
     * @throws ApiErrorException
     */
    protected function validateResponse(
        Response $response,
        array $request = [],
    ): void {
        if ($response->successful()) {
            return;
        }

        /** Without the flag only 422 and 500 throw, as they always have. */
        if ($this->config->strictErrors || in_array($response->status(), [422, 500], true)) {
            throw new ApiErrorException($response, $request);
        }
    }
}
