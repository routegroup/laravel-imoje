<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum Environment: string
{
    case PRODUCTION = 'production';
    case SANDBOX = 'sandbox';

    public function url(): string
    {
        return match ($this) {
            self::PRODUCTION => 'https://api.imoje.pl/v1',
            self::SANDBOX => 'https://sandbox.api.imoje.pl/v1',
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
