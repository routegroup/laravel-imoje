<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum PaymentMethod: string
{
    case CARD = 'card'; // Visa, MasterCard, Visa Mobile etc
    case PAY_BY_LINK = 'pbl'; // Online transfer
    case BLIK = 'blik';
    case PAYLATER = 'imoje_paylater'; // Twisto, PayPo, PragmaGO etc
    case LEASE = 'lease'; // ING Lease Now
    case WIRE_TRANSFER = 'wt'; // Wire transfer
    case ING = 'ing';
    case WALLET = 'wallet'; // Electronic wallets
    case IMOJE_INSTALLMENTS = 'imoje_installments';
    case TWISTO = 'twisto';
    case IDEAL = 'ideal';
    case PIS_PROXY = 'pis_proxy';

    /** @deprecated The gateway refuses it as a method; it is a PaymentMethodCode. */
    case VISA_MOBILE = 'visa_mobile';
}
