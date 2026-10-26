<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Concerns;

use Routegroup\Imoje\Payment\Exceptions\InvalidFieldException;

/**
 * One definition of the limits, shared by the API and both paywall channels,
 * so a payload cannot be refused through one and forwarded through the other.
 *
 * The patterns are the gateway's own, transcribed from the 422s it answers
 * with rather than from either specification — both document narrower sets
 * than it actually accepts, and `cid` is the clearest case: the schema allows
 * letters, digits and a hyphen, while the gateway takes `cid_1` quite happily.
 *
 * Where PCRE and the gateway's JavaScript read a class differently, these err
 * towards accepting. Being a shade more permissive costs a round trip the
 * gateway would have rejected anyway; being stricter would refuse calls that
 * work.
 */
trait ValidatesGatewayFields
{
    /** @var array<string, array{pattern: string, max: int}> */
    private const GATEWAY_RULES = [
        'orderId' => [
            'pattern' => '/^[A-Za-z0-9_\s\-#.\\\\\/\x{00C0}-\x{02C0}]+$/u',
            'max' => 100,
        ],
        'customerId' => [
            'pattern' => '/^[A-Za-z0-9_\s\-#.\\\\\/]+$/u',
            'max' => 36,
        ],
        'phone' => [
            'pattern' => '/^[+ \-0-9]*$/',
            'max' => 20,
        ],
        'name' => [
            'pattern' => '/^[0-9a-zA-Z\s\-#.\'`"&,_\\\\\/\x{00C0}-\x{02C0}\x{0400}-\x{04FF}]*$/u',
            'max' => 100,
        ],
        'description' => [
            'pattern' => '/^[0-9a-zA-Z\s\-#.\'`"&,_\\\\\/\x{00C0}-\x{02C0}]*$/u',
            'max' => 255,
        ],
        'email' => [
            'pattern' => '//',
            'max' => 200,
        ],
    ];

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, string>  $fields  attribute key => rule name
     *
     * @throws InvalidFieldException
     */
    protected static function checkGatewayFields(array $attributes, array $fields): void
    {
        foreach ($fields as $key => $rule) {
            $value = $attributes[$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            self::checkOne($key, $rule, (string) $value);
        }
    }

    /** @throws InvalidFieldException */
    private static function checkOne(string $key, string $rule, string $value): void
    {
        $limits = self::GATEWAY_RULES[$rule];

        if (mb_strlen($value) > $limits['max']) {
            throw new InvalidFieldException($key, "must be at most {$limits['max']} characters");
        }

        if ($rule === 'email') {
            if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidFieldException($key, 'must be an email address');
            }

            return;
        }

        if (preg_match($limits['pattern'], $value) !== 1) {
            throw new InvalidFieldException($key, 'carries a character the gateway refuses');
        }
    }
}
