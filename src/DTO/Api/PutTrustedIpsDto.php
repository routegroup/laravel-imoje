<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use JetBrains\PhpStorm\ArrayShape;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Api\PutTrustedIpsDtoFactory;

/**
 * @property-read array $trustedIps
 *
 * @method static PutTrustedIpsDtoFactory factory($count = null, $state = [])
 */
class PutTrustedIpsDto extends BaseDto
{
    use HasFactory;

    public function __construct(
        #[ArrayShape([
            // Required
            'trustedIps' => 'array',
        ])] array $attributes = []
    ) {
        parent::__construct($attributes);
    }

    protected static function newFactory(): PutTrustedIpsDtoFactory
    {
        return PutTrustedIpsDtoFactory::new();
    }
}
