<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

class Url
{
    public function __construct(
        public readonly Config $config,
    ) {}

    public function createTransactionUrl(): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'transaction',
        ]);
    }

    public function createGetTransactionUrl($transactionId): string
    {
        return implode('/', [
            $this->createTransactionUrl(),
            $transactionId,
        ]);
    }

    public function createPaymentUrl(): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'payment',
        ]);
    }

    public function createGetPaymentUrl($paymentId): string
    {
        return implode('/', [
            $this->createPaymentUrl(),
            $paymentId,
        ]);
    }

    public function createCancelPaymentUrl(): string
    {
        return implode('/', [
            $this->createPaymentUrl(),
            'cancel',
        ]);
    }

    public function createRefundUrl(string $transactionId): string
    {
        return implode('/', [
            $this->createTransactionUrl(),
            $transactionId,
            'refund',
        ]);
    }

    public function createChargeProfileUrl(): string
    {
        return implode('/', [
            $this->createTransactionUrl(),
            'profile',
        ]);
    }

    public function createProfileIdUrl(string $profileId): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'profile',
            'id',
            $profileId,
        ]);
    }

    public function createCaptureUrl(string $transactionId): string
    {
        return implode('/', [
            $this->createTransactionUrl(),
            $transactionId,
            'capture',
        ]);
    }

    public function createVoidUrl(string $transactionId): string
    {
        return implode('/', [
            $this->createTransactionUrl(),
            $transactionId,
            'void',
        ]);
    }

    public function createCanRefundUrl(string $transactionId): string
    {
        return implode('/', [
            $this->createTransactionUrl(),
            $transactionId,
            'can-refund',
        ]);
    }

    public function createProfileCidUrl(string $cid): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'profile',
            'cid',
            $cid,
        ]);
    }

    public function createProfileDeactivateUrl(): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'profile',
            'deactivate',
        ]);
    }

    public function createServiceUrl(string $serviceId): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'service',
            $serviceId,
        ]);
    }

    public function createServicesUrl(): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'services',
        ]);
    }

    public function createGetPaymentMethodsUrl(string $serviceId): string
    {
        return implode('/', [
            $this->createServiceUrl($serviceId),
            'get-payment-methods',
        ]);
    }

    public function createSettingsIpsUrl(): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'settings',
            'ips',
        ]);
    }
}
