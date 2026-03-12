<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\Casts\ServiceDataDto;
use Routegroup\Imoje\Payment\Factories\Responses\ServiceResponseDtoFactory;

/**
 * @property-read ServiceDataDto $service
 *
 * @method static ServiceResponseDtoFactory factory($count = null, $state = [])
 */
class ServiceResponseDto extends ResponseDto
{
    use HasFactory;

    protected array $casts = [
        'service' => ServiceDataDto::class,
    ];

    protected static function newFactory(): ServiceResponseDtoFactory
    {
        return ServiceResponseDtoFactory::new();
    }
}
