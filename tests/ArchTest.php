<?php

namespace Routegroup\Imoje\Payment\Tests;

it('will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

// BaseDto::castAttribute() only casts enums that implement BackedEnum, since
// from() exists there and not on UnitEnum. A pure enum added to Types would be
// passed through uncast instead, silently skipping the conversion.
it('only declares backed enums in Types')
    ->expect('Routegroup\Imoje\Payment\Types')
    ->toBeEnums()
    ->toBeStringBackedEnums();
