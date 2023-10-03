<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum WidgetType: string
{
    case ONECLICK = 'oneclick';
    case RECURRING = 'recurring';
    case ECOM3DS = 'ecom3ds';
}
