<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

class Url
{
    public function __construct(
        public readonly Config $config,
    ) {
    }

    public function createTransactionUrl(): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'transaction',
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

    public function createRefundUrl(string $transactionId): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'transaction',
            $transactionId,
            'refund',
        ]);
    }

    public function createChargeProfileUrl(): string
    {
        return implode('/', [
            $this->config->env->apiUrl(),
            'merchant',
            $this->config->merchantId,
            'transaction',
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
}
