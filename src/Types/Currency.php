<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum Currency: string
{
    case PLN = 'PLN';
    case EUR = 'EUR';
    case CZK = 'CZK';
    case GBP = 'GBP';
    case USD = 'USD';
    case UAH = 'UAH';
    case HRK = 'HRK';
    case HUF = 'HUF';
    case SEK = 'SEK';
    case RON = 'RON';
    case CHF = 'CHF';
    case BGN = 'BGN';
}
