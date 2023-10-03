<?php

namespace Routegroup\Imoje\Payment\Types;

enum ActionType: string
{
    case REDIRECT = 'redirect';
    case TRANSFER = 'transfer';
}
