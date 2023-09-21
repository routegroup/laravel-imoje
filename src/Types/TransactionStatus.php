<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum TransactionStatus: string
{
    case NEW = 'new';
    case AUTHORIZED = 'authorized';
    case PENDING = 'pending';
    case SUBMITTED_FOR_SETTLEMENT = 'submitted_for_settlement';
    case REJECTED = 'rejected';
    case SETTLED = 'settled';
    case ERROR = 'error';
    case CANCELLED = 'cancelled';
    case REFUND = 'refund';

    public function canChange(TransactionStatus $newStatus): bool
    {
        if ($newStatus === self::NEW) {
            return false;
        }

        if (
            in_array($this, [
                self::ERROR,
                self::CANCELLED,
                self::REJECTED,
                self::SETTLED,
                self::REFUND,
            ])
        ) {
            return false;
        }

        if ($this === $newStatus) {
            return false;
        }

        return true;
    }
}
