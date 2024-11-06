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
            self::PRODUCTION => 'https://api.imoje.pl/v1',
            self::SANDBOX => 'https://sandbox.api.imoje.pl/v1',
        };
    }

    public function paywallUrl(?Lang $lang = null): string
    {
        if ($lang) {
            return match ($this) {
                self::PRODUCTION => "https://paywall.imoje.pl/$lang->value/payment",
                self::SANDBOX => "https://sandbox.paywall.imoje.pl/$lang->value/payment",
            };
        }

        return match ($this) {
            self::PRODUCTION => 'https://paywall.imoje.pl/payment',
            self::SANDBOX => 'https://sandbox.paywall.imoje.pl/payment',
        };
    }

    public function widgetUrl(): string
    {
        return match ($this) {
            self::PRODUCTION => 'https://paywall.imoje.pl/js/widget.min.js',
            self::SANDBOX => 'https://sandbox.paywall.imoje.pl/js/widget.min.js',
        };
    }
}
