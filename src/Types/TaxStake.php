<?php

namespace Routegroup\Imoje\Payment\Types;

enum TaxStake: string
{
    case TAX_23 = 'TAX_23';
    case TAX_22 = 'TAX_22';
    case TAX_8 = 'TAX_8';
    case TAX_7 = 'TAX_7';
    case TAX_5 = 'TAX_5';
    case TAX_3 = 'TAX_3';
    case TAX_0 = 'TAX_0';
    case TAX_EXEMPT = 'TAX_EXEMPT';
    case TAX_NOT_LIABLE = 'TAX_NOT_LIABLE';
    case TAX_REVERSE_CHARGE = 'TAX_REVERSE_CHARGE';
    case TAX_EXCLUDING = 'TAX_EXCLUDING';

    /** The gateway also accepts a bare percentage, so 23 resolves to TAX_23. */
    public static function fromPercentage(int|string $value): ?self
    {
        return self::tryFrom((string) $value) ?? self::tryFrom('TAX_'.$value);
    }
}
