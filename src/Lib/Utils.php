<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Illuminate\Contracts\Support\Arrayable;
use Routegroup\Imoje\Payment\Types\Environment;
use Routegroup\Imoje\Payment\Types\HashMethod;

class Utils
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $serviceId,
        public readonly string $serviceKey,
        public readonly string $apiKey,
        public readonly Environment $env,
    ) {
    }

    public function createSignature(
        array $orderData,
        HashMethod $hashMethod = HashMethod::SHA256
    ): string {
        $hash = hash($hashMethod->value, $this->buildQuery($orderData).$this->serviceKey);

        return "{$hash};{$hashMethod->value}";
    }

    public function buildQuery(array $orderData): string
    {
        ksort($orderData);

        $orderData = $this->transformValues($orderData);
        $data = [];

        foreach ($orderData as $key => $value) {
            $data[] = $key.'='.$value;
        }

        return implode('&', $data);
    }

    public function transformValues(array $values): array
    {
        $computed = [];

        foreach ($values as $key => $value) {
            $result = $value;

            if ($result instanceof Arrayable) {
                $result = $result->toArray();
            }

            if (is_object($result) && enum_exists(get_class($result))) {
                $result = $result->value;
            }

            $computed[$key] = $result;
        }

        return $computed;
    }

    public function createRefundUrl(string $transactionId): string
    {
        return implode('/', [
            $this->env->url(),
            'merchant',
            $this->merchantId,
            'transaction',
            $transactionId,
            'refund',
        ]);
    }

    public function createChargeProfileUrl(): string
    {
        return implode('/', [
            $this->env->url(),
            'merchant',
            $this->merchantId,
            'transaction',
            'profile',
        ]);
    }

    public function createDeactivateProfileUrl(string $profileId): string
    {
        return implode('/', [
            $this->env->url(),
            'merchant',
            $this->merchantId,
            'profile',
            'id',
            $profileId,
        ]);
    }
}
