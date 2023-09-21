<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum HashMethod: string
{
    case SHA224 = 'sha224';
    case SHA256 = 'sha256';
    case SHA384 = 'sha384';
    case SHA512 = 'sha512';
}
