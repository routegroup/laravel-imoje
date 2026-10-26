<?php

namespace Routegroup\Imoje\Payment\Types;

enum CartItemState: string
{
    case NEW = 'NEW';
    case USED = 'USED';
}
