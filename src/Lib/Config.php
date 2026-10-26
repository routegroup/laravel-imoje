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
        public readonly bool $strictErrors = false,
        /** Laravel's own defaults, so an existing integration keeps the timings it already had. */
        public readonly int|float $timeout = 30,
        public readonly int|float $connectTimeout = 10,
    ) {}
}
