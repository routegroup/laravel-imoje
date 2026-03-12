<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\Casts\ServiceDataDto;
use Routegroup\Imoje\Payment\Factories\Responses\ServicesResponseDtoFactory;

/**
 * @property-read ServiceDataDto[] $services
 *
 * @method static ServicesResponseDtoFactory factory($count = null, $state = [])
 */
class ServicesResponseDto extends ResponseDto
{
    use HasFactory;

    public function __construct($incomingData)
    {
        parent::__construct($incomingData);
        $raw = $this->attributes;
        $services = array_map(
            fn (array $item): ServiceDataDto => new ServiceDataDto($item['service'] ?? []),
            is_array($raw) && array_is_list($raw) ? $raw : []
        );
        $this->attributes = ['services' => $services];
    }

    protected static function newFactory(): ServicesResponseDtoFactory
    {
        return ServicesResponseDtoFactory::new();
    }
}
