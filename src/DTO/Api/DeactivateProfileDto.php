<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Api\DeactivateProfileDtoFactory;

/**
 * @property-read string $paymentProfileId
 *
 * @method static DeactivateProfileDtoFactory factory($count = null, $state = [])
 */
class DeactivateProfileDto extends BaseDto
{
    use HasFactory;

    /**
     * @param array{
     *     // Required
     *     paymentProfileId?: string,
     * } $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected static function newFactory(): DeactivateProfileDtoFactory
    {
        return DeactivateProfileDtoFactory::new();
    }
}
