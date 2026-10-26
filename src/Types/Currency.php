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
    case HUF = 'HUF';
    case SEK = 'SEK';
    case RON = 'RON';
    case CHF = 'CHF';
    case BGN = 'BGN';

    /** @deprecated The gateway answers 422 for it, and no specification mentions it. */
    case UAH = 'UAH';

    /** @deprecated Retired in 2023, and the gateway answers 422 for it. */
    case HRK = 'HRK';
}
