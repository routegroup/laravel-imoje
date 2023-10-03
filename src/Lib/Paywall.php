<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto;
use Routegroup\Imoje\Payment\Types\Lang;

class Paywall
{
    public function __construct(
        protected readonly Config $config
    ) {
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
