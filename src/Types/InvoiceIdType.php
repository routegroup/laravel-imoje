<?php

namespace Routegroup\Imoje\Payment\Types;

enum InvoiceIdType: string
{
    case ID = 'ID';
    case VAT_ID = 'VAT_ID';
}
