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
    case CB = 'cb';
    case CITI = 'citi';
    case CREDITAGRICOLE = 'creditagricole';
    case CS = 'cs';
    case ENVELO = 'envelo';
    case GETIN = 'getin';
    case GOOGLE_PAY = 'gpay';
    case IDEABANK = 'ideabank';
    case IMOJE_INSTALLMENTS = 'imoje_installments';
    case INBANK = 'inbank';
    case INBANK_0 = 'inbank_0';
    case ING = 'ing';
    case INTELIGO = 'inteligo';
    case IPKO = 'ipko';
    case KB = 'kb';
    case MILLENNIUM = 'millennium';
    case MP = 'mp';
    case MTRANSFER = 'mtransfer';
    case NEST = 'nest';
    case NOBLE = 'noble';
    case PAYLATER = 'imoje_twisto';
    case PAYPAL = 'paypal';
    case PAYPO = 'paypo';
    case PBS = 'pbs';
    case PEKAO24 = 'pekao24';
    case PF = 'pf';
    case PG = 'pg';
    case PLUSBANK = 'plusbank';
    case POCZTOWY = 'pocztowy';
    case POSTA = 'posta';
    case PRAGMA_GO = 'pragma_go';
    case RF = 'rf';
    case SPORO = 'sporo';
    case TATRA = 'tatra';
    case TMOBILE = 'tmobile';
    case UC = 'uc';
    case VIAMO = 'viamo';
    case VISA_MOBILE = 'visa_mobile';
    case VUB = 'vub';
    case WIRE_TRANSFER = 'wt';
    case WIRE_TRANSFER_SPLIT = 'wt_split';

    // For notification
    case ONECLICK = 'oneclick';
    case RECURRING = 'recurring';
    case ECOM3DS = 'ecom3ds';

    public function getLogo(?string $extension = null): ?string
    {
        $filename = $this->getLogoFilename($extension);

        if (! $filename) {
            return null;
        }

        return Environment::PRODUCTION->cdnUrl().'/img/pay/'.$filename;
    }

    public function getLogoFilename(?string $extension = null): ?string
    {
        $filename = match ($this) {
            self::ALIOR => 'alior.svg',
            self::APPLEPAY => 'applepay.svg',
            self::BLIK => 'blik.png', // missing SVG
            self::BLIK_PAYLATER => 'blik_paylater.png', // missing SVG
            self::BNPPARIBAS => 'bnpparibas.png',
            self::BOS => 'bos.png', // missing SVG
            self::BS => 'bs.png',
            self::BSPB => 'bspb.png', // missing SVG
            self::BZWBK => 'bzwbk.png',
            self::CB => 'cb.svg',
            self::CITI => 'citi.png', // missing SVG
            self::CREDITAGRICOLE => 'creditagricole.svg',
            self::CS => 'cs.svg',
            self::ENVELO => 'envelo.png', // missing SVG
            self::GETIN => 'getin.svg',
            self::GOOGLE_PAY => 'gpay.svg',
            self::IDEABANK => 'ideabank.png', // missing SVG
            self::INBANK => 'inbank.png', // missing SVG
            self::ING => 'ing.png', // missing SVG
            self::INTELIGO => 'inteligo.png', // missing SVG
            self::IPKO => 'ipko.png',
            self::KB => 'kb.svg',
            self::MILLENNIUM => 'millennium.svg',
            self::MTRANSFER => 'mtransfer.png', // missing SVG
            self::NEST => 'nest.svg',
            self::NOBLE => 'noble.png', // missing SVG
            self::PAYPO => 'paypo.svg',
            self::PAYPAL => 'paypal.png', // missing SVG
            self::PBS => 'pbs.png', // missing SVG
            self::PEKAO24 => 'pekao24.svg',
            self::PF => 'pf.svg',
            self::PG => 'pg.svg',
            self::PLUSBANK => 'plusbank.png', // missing SVG
            self::POCZTOWY => 'pocztowy.svg',
            self::POSTA => 'posta.svg',
            self::PRAGMA_GO => 'pragma_go.svg',
            self::RF => 'rf.svg',
            self::SPORO => 'sporo.svg',
            self::TATRA => 'tatra.svg',
            self::TMOBILE => 'tmobile.png', // missing SVG
            self::UC => 'uc.svg',
            self::VIAMO => 'viamo.svg',
            self::VISA_MOBILE => 'visa_mobile.svg',
            self::VUB => 'vub.svg',
            self::WIRE_TRANSFER => 'wt.svg',
            self::WIRE_TRANSFER_SPLIT => 'wt_split.svg',
            default => null
        };

        if ($filename === null) {
            return null;
        }

        if ($extension !== null) {
            $base = pathinfo($filename, PATHINFO_FILENAME);

            $ext = ltrim($extension, '.');

            return "{$base}.{$ext}";
        }

        return $filename;
    }
}
