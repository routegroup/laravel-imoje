<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum PaymentMethodCode: string
{
    case ALIOR = 'alior';
    case APPLEPAY = 'applepay';
    case BLIK = 'blik';
    case BLIK_ONECLICK = 'blik_oneclick';
    case BLIK_PAYLATER = 'blik_paylater';
    case BNPPARIBAS = 'bnpparibas';
    case BOS = 'bos';
    case BS = 'bs';
    case BSPB = 'bspb';
    case BZWBK = 'bzwbk';
    case CITI = 'citi';
    case CREDITAGRICOLE = 'creditagricole';
    case ENVELO = 'envelo';
    case GETIN = 'getin';
    case GOOGLE_PAY = 'gpay';
    case IDEABANK = 'ideabank';
    case IMOJE_INSTALLMENTS = 'imoje_installments';
    case ING = 'ing';
    case INTELIGO = 'inteligo';
    case IPKO = 'ipko';
    case MILLENNIUM = 'millennium';
    case MTRANSFER = 'mtransfer';
    case NEST = 'nest';
    case NOBLE = 'noble';
    case PAYLATER = 'imoje_twisto';
    case PAYPO = 'paypo';
    case PBS = 'pbs';
    case PEKAO24 = 'pekao24';
    case PLUSBANK = 'plusbank';
    case POCZTOWY = 'pocztowy';
    case PRAGMA_GO = 'pragma_go';
    case TMOBILE = 'tmobile';
    case VISA_MOBILE = 'visa_mobile';
    case WIRE_TRANSFER = 'wt';

    // For notification
    case ONECLICK = 'oneclick';
    case RECURRING = 'recurring';
    case ECOM3DS = 'ecom3ds';

    public function getLogo(): ?string
    {
        $filename = $this->getLogoFilename();

        if (! $filename) {
            return null;
        }

        return Environment::PRODUCTION->cdnUrl().'/img/pay/'.$filename;
    }

    public function getLogoFilename(): ?string
    {
        return match ($this) {
            self::ALIOR => 'alior.svg',
            self::BNPPARIBAS => 'bnpparibas.png',
            self::BOS => 'bos.png',
            self::BS => 'bs.png',
            self::BSPB => 'bspb.png',
            self::BZWBK => 'bzwbk.png',
            self::CITI => 'citi.png',
            self::CREDITAGRICOLE => 'creditagricole.svg',
            self::ENVELO => 'envelo.png',
            self::GETIN => 'getin.svg',
            self::IDEABANK => 'ideabank.png',
            self::ING => 'ing.png',
            self::INTELIGO => 'inteligo.png',
            self::IPKO => 'ipko.png',
            self::MILLENNIUM => 'millennium.svg',
            self::MTRANSFER => 'mtransfer.png',
            self::NEST => 'nest.svg',
            self::NOBLE => 'noble.png',
            self::PBS => 'pbs.png',
            self::PEKAO24 => 'pekao24.svg',
            self::PLUSBANK => 'plusbank.png',
            self::POCZTOWY => 'pocztowy.svg',
            self::TMOBILE => 'tmobile.svg',
            self::PAYPO => 'paypo.svg',
            default => null
        };
    }
}
