<?php

namespace Routegroup\Imoje\Payment\Tests;

it('will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();
