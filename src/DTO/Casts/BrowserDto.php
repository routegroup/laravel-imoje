<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidWalletException;
use Routegroup\Imoje\Payment\Factories\Casts\BrowserDtoFactory;

/**
 * @property-read string $ip
 * @property-read string $language
 * @property-read bool $jsEnabled
 * @property-read int $timezoneOffset
 * @property-read string $userAgent
 * @property-read string $accept
 * @property-read bool $javaEnabled
 * @property-read int $screenColorDepth
 * @property-read int $screenHeight
 * @property-read int $screenWidth
 *
 * @method static BrowserDtoFactory factory($count = null, $state = [])
 */
class BrowserDto extends BaseDto
{
    use HasFactory;

    /** The gateway types these strictly: '100' and 'true' are both refused. */
    protected array $casts = [
        'jsEnabled' => 'bool',
        'javaEnabled' => 'bool',
        'timezoneOffset' => 'int',
        'screenColorDepth' => 'int',
        'screenHeight' => 'int',
        'screenWidth' => 'int',
    ];

    /** @throws InvalidWalletException */
    public function __construct($attributes = [])
    {
        $attributes = (array) $attributes;

        foreach ([
            'ip', 'language', 'jsEnabled', 'timezoneOffset', 'userAgent',
            'accept', 'javaEnabled', 'screenColorDepth', 'screenHeight', 'screenWidth',
        ] as $field) {
            if (($attributes[$field] ?? null) === null || $attributes[$field] === '') {
                throw new InvalidWalletException("additionalData.browser.$field", 'is required');
            }
        }

        parent::__construct($attributes);
    }

    protected static function newFactory(): BrowserDtoFactory
    {
        return BrowserDtoFactory::new();
    }
}
