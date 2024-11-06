<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Routegroup\Imoje\Payment\Types\Environment;

class Config
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $serviceId,
        public readonly string $serviceKey,
        public readonly string $apiKey,
        public readonly Environment $env,
    ) {}
}
