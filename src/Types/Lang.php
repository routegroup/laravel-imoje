<?php

namespace Routegroup\Imoje\Payment\Types;

enum Lang: string
{
    case PL = 'pl';
    case EN = 'en';
    case CS = 'cs';
    case DE = 'de';
    case ES = 'es';
    case FR = 'fr';
    case IT = 'it';
    case LT = 'lt';
    case RU = 'ru';
    case SK = 'sk';
    case SL = 'sl';
    case UK = 'uk';
    case NL = 'nl';
    case HU = 'hu';
    case RO = 'ro';
    case BG = 'bg';
    case SV = 'sv';

    /** @deprecated ING uses `uk` for Ukrainian. Resolves to Lang::UK; use that instead. */
    case UA = 'ua';

    public function canonical(): self
    {
        return $this === self::UA ? self::UK : $this;
    }
}
