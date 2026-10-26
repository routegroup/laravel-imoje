<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\Factories\Responses\TrustedIpsResponseDtoFactory;

/**
 * One DTO for two disjoint shapes: GET answers {trustedIps}, PUT answers
 * {status}, so exactly one of the two is ever set. Splitting them would change
 * putTrustedIps()'s return type, which the 1.x line does not allow.
 *
 * @property-read array|null $trustedIps Set by GET
 * @property-read string|null $status Set by PUT
 *
 * @method static TrustedIpsResponseDtoFactory factory($count = null, $state = [])
 */
class TrustedIpsResponseDto extends ResponseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected static function newFactory(): TrustedIpsResponseDtoFactory
    {
        return TrustedIpsResponseDtoFactory::new();
    }
}
