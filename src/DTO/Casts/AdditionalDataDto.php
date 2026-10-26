<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidWalletException;
use Routegroup\Imoje\Payment\Factories\Casts\AdditionalDataDtoFactory;

/**
 * @property-read BrowserDto $browser
 *
 * @method static AdditionalDataDtoFactory factory($count = null, $state = [])
 */
class AdditionalDataDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'browser' => BrowserDto::class,
    ];

    /** @throws InvalidWalletException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        if (! isset($attributes['browser'])) {
            throw new InvalidWalletException('additionalData.browser', 'is required');
        }

        parent::__construct($attributes);
    }

    protected static function newFactory(): AdditionalDataDtoFactory
    {
        return AdditionalDataDtoFactory::new();
    }
}
