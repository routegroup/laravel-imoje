<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum PaymentMethod: string
{
    case BLIK = 'blik';
    case PAYLATER = 'imoje_paylater';
    case PBL = 'pbl';
    case CARD = 'card';
    case ING = 'ing';
}
