<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Types\HashMethod;

class Utils
{
    public function __construct(
        protected readonly Config $config,
    ) {}

    public function createSignature(
        array $orderData,
        HashMethod $hashMethod = HashMethod::SHA256
    ): string {
        $hash = hash($hashMethod->value, $this->buildQuery($orderData).$this->config->serviceKey);

        return "{$hash};{$hashMethod->value}";
    }

    public function verifySignature(
        string $signature,
        string $rawBody,
        HashMethod $hashMethod
    ): bool {
        return hash_equals(
            hash($hashMethod->value, $rawBody.$this->config->serviceKey),
            $signature
        );
    }

    /**
     * @param  array<array-key, mixed>  $orderData
     */
    public function buildQuery(array $orderData): string
    {
        return $this->prepareData($orderData);
    }

    /**
     * Mirrors the reference prepareData() published in the paywall
     * documentation: sort by key at every level, write a nested key as
     * parent[child], join the pairs with '&'.
     *
     * A null is skipped rather than emitted as "key=". The request itself is
     * built with Arr::query(), which drops nulls, so emitting one here would
     * sign a parameter the gateway never receives.
     *
     * @param  array<array-key, mixed>  $data
     */
    private function prepareData(array $data, string $prefix = ''): string
    {
        $data = $this->transformValues($data);

        ksort($data);

        $pairs = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $key = $prefix === '' ? (string) $key : $prefix.'['.$key.']';

            $pair = is_array($value)
                ? $this->prepareData($value, $key)
                : $key.'='.$value;

            if ($pair !== '') {
                $pairs[] = $pair;
            }
        }

        return implode('&', $pairs);
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

    public function mockHeaders(
        BaseDto $dto,
        HashMethod $hashMethod = HashMethod::SHA256
    ): array {
        $body = json_encode($dto->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = hash($hashMethod->value, $body.$this->config->serviceKey);

        $value = "merchantid={$this->config->merchantId};";
        $value .= "serviceid={$this->config->serviceId};";
        $value .= "signature=$signature;";
        $value .= "alg=$hashMethod->value";

        return ['x-imoje-signature' => $value];
    }

    public function hasStructure(array $data, array $structure): bool
    {
        $structure = Arr::dot($structure);
        $data = Arr::dot($data);

        return count($structure) === count(array_intersect($structure, $data));
    }
}
