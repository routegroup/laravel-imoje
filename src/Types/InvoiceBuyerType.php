<?php

namespace Routegroup\Imoje\Payment\Types;

enum InvoiceBuyerType: string
{
    case PERSON = 'PERSON';
    case COMPANY = 'COMPANY';
}
