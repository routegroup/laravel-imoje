<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum Environment: string
{
    case PRODUCTION = 'production';
    case SANDBOX = 'sandbox';

    public function apiUrl(): string
    {
        return match ($this) {
            self::PRODUCTION => 'https://api.pay.ing.pl/v1',
            self::SANDBOX => 'https://api.sandbox.pay.ing.pl/v1',
        };
    }

    public function paywallUrl(?Lang $lang = null): string
    {
        if ($lang) {
            $locale = $lang->value;

            return match ($this) {
                self::PRODUCTION => "https://paywall.pay.ing.pl/$locale/payment",
                self::SANDBOX => "https://paywall.sandbox.pay.ing.pl/$locale/payment",
            };
        }

        return match ($this) {
            self::PRODUCTION => 'https://paywall.pay.ing.pl/payment',
            self::SANDBOX => 'https://paywall.sandbox.pay.ing.pl/payment',
        };
    }

    public function widgetUrl(): string
    {
        return match ($this) {
            self::PRODUCTION => 'https://paywall.pay.ing.pl/js/widget.min.js',
            self::SANDBOX => 'https://paywall.sandbox.pay.ing.pl/js/widget.min.js',
        };
    }

    public function installmentsUrl(): string
    {
        return match ($this) {
            self::PRODUCTION => 'https://paywall.pay.ing.pl/js/installments.js',
            self::SANDBOX => 'https://paywall.sandbox.pay.ing.pl/js/installments.js',
        };
    }

    public function cdnUrl(): string
    {
        return 'https://cdn.pay.ing.pl';
    }
}
