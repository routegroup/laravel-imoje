<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Api\RefundDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\TransactionType;

/**
 * @property-read int $amount
 * @property-read TransactionType $type
 * @property-read string $serviceId
 * @property-read string|null $title
 * @property-read bool|null $sendRefundConfirmationEmail
 *
 * @method static RefundDtoFactory factory($count = null, $state = [])
 */
class RefundDto extends BaseDto
{
    use HasFactory;

    /**
     * sendRefundConfirmationEmail is deliberately absent. BaseDto materialises
     * every key listed here, and (bool) null is false, so declaring it would
     * put sendRefundConfirmationEmail=false in every request and silently
     * switch off the payer's confirmation e-mail. It is cast below instead,
     * only when the caller actually passed it.
     */
    protected array $casts = [
        'amount' => 'int',
        'type' => TransactionType::class,
    ];

    /**
     * @param array{
     *     // Required
     *     amount?: int,
     *     // Required but passed by default
     *     type?: TransactionType|string,
     *     serviceId?: string,
     *     // Optional
     *     title?: string,
     *     sendRefundConfirmationEmail?: bool,
     * } $attributes
     */
    public function __construct(array $attributes = [])
    {
        $config = app(Config::class);

        $attributes = array_merge([
            'type' => TransactionType::REFUND,
            'serviceId' => $config->serviceId,
        ], $attributes);

        // Cast in place rather than through $casts, so an omitted flag stays
        // omitted while an explicit false is still sent as false. allowNull
        // would not do: it filters out every falsy value, false included.
        if (array_key_exists('sendRefundConfirmationEmail', $attributes)) {
            $attributes['sendRefundConfirmationEmail'] = (bool) $attributes['sendRefundConfirmationEmail'];
        }

        parent::__construct($attributes);
    }

    protected static function newFactory(): RefundDtoFactory
    {
        return RefundDtoFactory::new();
    }
}
