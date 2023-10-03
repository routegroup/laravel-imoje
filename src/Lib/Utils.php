<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use Illuminate\Contracts\Support\Arrayable;

class Utils
{
    public function __construct(
        public readonly Config $config,
    ) {
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
}
