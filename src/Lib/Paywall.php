<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto;
use Routegroup\Imoje\Payment\Types\HashMethod;
use Routegroup\Imoje\Payment\Types\Lang;

class Paywall
{
    public function __construct(
        public readonly Config $config,
        public readonly Utils $utils,
    ) {
    }

    public function createSignature(
        array $orderData,
        HashMethod $hashMethod = HashMethod::SHA256
    ): string {
        $hash = hash($hashMethod->value, $this->buildQuery($orderData).$this->config->serviceKey);

        return "{$hash};{$hashMethod->value}";
    }

    public function buildQuery(array $orderData): string
    {
        ksort($orderData);

        $orderData = $this->utils->transformValues($orderData);
        $data = [];

        foreach ($orderData as $key => $value) {
            $data[] = $key.'='.$value;
        }

        return implode('&', $data);
    }

    public function createTransaction(
        TransactionDto $dto,
        Lang $lang = null
    ): RedirectResponse {
        $query = Arr::query($dto->toArray());
        $url = $this->config->env->paywallUrl($lang)."?$query";

        return redirect($url);
    }
}
