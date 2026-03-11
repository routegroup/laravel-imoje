<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\Factories\Responses\TrustedIpsResponseDtoFactory;

/**
 * @property-read array $trustedIps
 * @property-read string|null $status
 *
 * @method static TrustedIpsResponseDtoFactory factory($count = null, $state = [])
 */
class TrustedIpsResponseDto extends ResponseDto
{
    use HasFactory;

    protected static function newFactory(): TrustedIpsResponseDtoFactory
    {
        return TrustedIpsResponseDtoFactory::new();
    }
}
