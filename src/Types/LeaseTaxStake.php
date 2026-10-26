<?php

namespace Routegroup\Imoje\Payment\Types;

/** Lease Now uses its own rates: bare percentages, not the invoice's TAX_23 spelling. */
enum LeaseTaxStake: string
{
    case RATE_23 = '23';
    case RATE_22 = '22';
    case RATE_8 = '8';
    case RATE_7 = '7';
    case RATE_5 = '5';
    case RATE_3 = '3';
    case RATE_0 = '0';
    case TAX_EXEMPT = 'TAX_EXEMPT';
    case TAX_NOT_LIABLE = 'TAX_NOT_LIABLE';
    case TAX_REVERSE_CHARGE = 'TAX_REVERSE_CHARGE';
    case TAX_EXCLUDING = 'TAX_EXCLUDING';

    /** The gateway takes a percentage as a number and rejects the same digits quoted. */
    public function encoded(): int|string
    {
        return is_numeric($this->value) ? (int) $this->value : $this->value;
    }

    /** Accepts 23, '23' and the invoice's TAX_23, so either vocabulary works. */
    public static function fromRate(int|string $value): ?self
    {
        return self::tryFrom((string) $value)
            ?? self::tryFrom((string) preg_replace('/^TAX_(\d+)$/', '$1', (string) $value));
    }
}
