<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Lib;

use JsonSchema\Validator;
use Routegroup\Imoje\Payment\Exceptions\ValidationException;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\TransactionStatus;

class Validate
{
    /**
     * @throws ValidationException
     */
    public static function notification(array $data): bool
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'transaction' => [
                    'type' => 'object',
                    'properties' => [
                        'amount' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'exclusiveMinimum' => true,
                        ],
                        'currency' => [
                            'type' => 'string',
                            'enum' => array_column(Currency::cases(), 'value'),
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => array_column(TransactionStatus::cases(), 'value'),
                        ],
                        'orderId' => [
                            'type' => 'string',
                        ],
                        'serviceId' => [
                            'type' => 'string',
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => [
                                'sale',
                                'refund',
                            ],
                        ],
                    ],
                    'required' => [
                        'amount',
                        'currency',
                        'status',
                        'orderId',
                        'serviceId',
                        'type',
                    ],
                ],
                'payment' => [
                    'type' => 'object',
                ],
            ],
        ];

        return self::validate($data, $schema, 'notification');
    }

    /**
     * @throws ValidationException
     */
    private static function validate(
        array $data,
        array $schema,
        string $schemaType
    ): bool {
        $objectData = json_decode(json_encode($data));
        $objectSchema = json_decode(json_encode($schema));

        $validator = new Validator();
        $validator->validate($objectData, $objectSchema);

        if ($validator->isValid()) {
            return true;
        }

        $errors = [
            'schema' => $schemaType,
        ];

        foreach ($validator->getErrors() as $error) {
            $errors[$error['property']] = $error['message'];
        }

        throw new ValidationException($errors);
    }
}
