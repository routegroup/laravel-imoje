<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum PaymentMethodCode: string
{
    case BLIK = 'blik';
    case ALIOR = 'alior';
    case BNPPARIBAS = 'bnpparibas';
    case BOS = 'bos';
    case BS = 'bs';
    case BSPB = 'bspb';
    case BZWBK = 'bzwbk';
    case CITI = 'citi';
    case CREDITAGRICOLE = 'creditagricole';
    case ENVELO = 'envelo';
    case GETIN = 'getin';
    case IDEABANK = 'ideabank';
    case ING = 'ing';
    case INTELIGO = 'inteligo';
    case IPKO = 'ipko';
    case MILLENNIUM = 'millennium';
    case MTRANSFER = 'mtransfer';
    case NEST = 'nest';
    case NOBLE = 'noble';
    case PBS = 'pbs';
    case PEKAO24 = 'pekao24';
    case PLUSBANK = 'plusbank';
    case POCZTOWY = 'pocztowy';
    case TMOBILE = 'tmobile';
    case PAYLATER = 'imoje_twisto';
    case BLIK_ONECLICK = 'blik_oneclick';
    case PAYPO = 'paypo';

    case ONECLICK = 'oneclick';
    case RECURRING = 'recurring';
    case ECOM3DS = 'ecom3ds';
}
