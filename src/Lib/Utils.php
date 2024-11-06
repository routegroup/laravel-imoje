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
        array $body,
        HashMethod $hashMethod
    ): bool {
        $body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $signature === hash($hashMethod->value, $body.$this->config->serviceKey);
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

    public function mockHeaders(
        BaseDto $dto,
        HashMethod $hashMethod = HashMethod::SHA256
    ): array {
        $body = json_encode($dto->toArray(), JSON_UNESCAPED_SLASHES);
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
