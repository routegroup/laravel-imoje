<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Paywall;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\HtmlString;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoiceDto;
use Routegroup\Imoje\Payment\DTO\Concerns\HasMultipayout;
use Routegroup\Imoje\Payment\DTO\Concerns\ValidatesGatewayFields;
use Routegroup\Imoje\Payment\Factories\Paywall\OneClickDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\HashMethod;
use Routegroup\Imoje\Payment\Types\Lang;
use Routegroup\Imoje\Payment\Types\WidgetType;

/**
 * The card widget, which covers all three of WidgetType: oneclick, recurring
 * and ecom3ds. The name is narrower than the thing for history's sake.
 *
 * @property-read string $serviceId
 * @property-read string $merchantId
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $orderId
 * @property-read string $customerId
 * @property-read string $customerFirstName
 * @property-read string $customerLastName
 * @property-read string $customerEmail
 * @property-read string $signature
 * @property-read string $customerPhone
 * @property-read string $orderDescription
 * @property-read string $urlSuccess
 * @property-read string $urlFailure
 * @property-read string $urlReturn
 * @property-read string $urlCancel
 * @property-read WidgetType $widgetType
 * @property-read int $validTo
 * @property-read string|null $invoice
 * @property-read array|null $multipayout
 * @property-read Lang|null $locale
 * @property-read bool|null $inline
 * @property-read string|null $elementId
 * @property-read string|null $elementEvent
 *
 * @method static OneClickDtoFactory factory($count = null, $state = [])
 */
class OneClickDto extends BaseDto
{
    use HasFactory;
    use HasMultipayout;
    use ValidatesGatewayFields;

    /** The widget refuses to initialise under any other id. */
    public const SCRIPT_ID = 'imoje-widget__script';

    /** Configuration of the widget itself, which the chapter excludes from the signature. */
    public const UNSIGNED = ['locale', 'inline', 'elementId', 'elementEvent'];

    protected bool $strictEnums = true;

    protected array $casts = [
        'amount' => 'int',
        'currency' => Currency::class,
        'widgetType' => WidgetType::class,
        'locale' => Lang::class,
        'inline' => 'bool',
    ];

    /**
     * @param array{
     *     // Required
     *     amount?: int,
     *     currency?: Currency|string,
     *     orderId?: string,
     *     customerId?: string,
     *     customerFirstName?: string,
     *     customerLastName?: string,
     *     customerEmail?: string,
     *     // Required but provided
     *     serviceId?: string,
     *     merchantId?: string,
     *     widgetType?: WidgetType|string,
     *     signature?: string,
     *     // Optional
     *     customerPhone?: string,
     *     orderDescription?: string,
     *     urlSuccess?: string,
     *     urlFailure?: string,
     *     urlReturn?: string,
     *     urlCancel?: string,
     *     validTo?: int,
     *     invoice?: InvoiceDto|array<mixed>,
     *     multipayout?: array<mixed>,
     *     // Configuration, excluded from the signature
     *     locale?: Lang|string,
     *     inline?: bool,
     *     elementId?: string,
     *     elementEvent?: string,
     * } $attributes
     */
    public function __construct($attributes = [], HashMethod $hashMethod = HashMethod::SHA256)
    {
        $config = app(Config::class);
        $utils = app(Utils::class);

        $attributes = array_merge([
            'serviceId' => $config->serviceId,
            'merchantId' => $config->merchantId,
        ], $attributes);

        self::checkGatewayFields($attributes, [
            'orderId' => 'orderId',
            'orderDescription' => 'description',
            'customerFirstName' => 'name',
            'customerLastName' => 'name',
            'customerPhone' => 'phone',
            'customerId' => 'customerId',
            'customerEmail' => 'email',
        ]);

        $attributes = self::castMultipayout($attributes);

        if (! array_key_exists('widgetType', $attributes)) {
            $attributes['widgetType'] = WidgetType::ONECLICK->value;
        }

        if (isset($attributes['invoice'])) {
            $invoice = $attributes['invoice'];
            $attributes['invoice'] = $invoice instanceof InvoiceDto
                ? $invoice->encoded()
                : (is_array($invoice) ? (new InvoiceDto($invoice))->encoded() : $invoice);
        }

        $attributes['signature'] = $utils->createSignature(
            array_diff_key($attributes, array_flip(self::UNSIGNED)),
            $hashMethod,
        );

        parent::__construct($attributes);
    }

    /**
     * Every attribute the script tag carries, named the way the widget reads
     * them. src and id belong here and nowhere near the signature.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $rendered = ['src' => app(Config::class)->env->widgetUrl(), 'id' => self::SCRIPT_ID];

        foreach ($this->flattenMultipayout($this->toArray()) as $key => $value) {
            if ($value === null) {
                continue;
            }

            $rendered['data-'.self::kebab($key)] = match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                is_array($value) => (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                default => (string) $value,
            };
        }

        return $rendered;
    }

    /**
     * @param  array<string, string|bool>  $extra  Anything the page needs on the tag, a CSP nonce above all
     */
    public function script(array $extra = []): HtmlString
    {
        $parts = [];

        foreach (array_merge($this->attributes(), $extra) as $name => $value) {
            if ($value === false) {
                continue;
            }

            $parts[] = $value === true
                ? htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8')
                : sprintf(
                    '%s="%s"',
                    htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
                );
        }

        return new HtmlString('<script '.implode(' ', $parts).'></script>');
    }

    private static function kebab(string $key): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $key));
    }

    protected static function newFactory(): OneClickDtoFactory
    {
        return OneClickDtoFactory::new();
    }
}
