<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Types;

enum TransactionStatus: string
{
    case NEW = 'new';
    case AUTHORIZED = 'authorized';
    case PENDING = 'pending';
    case REJECTED = 'rejected';
    case SETTLED = 'settled';
    case ERROR = 'error';
    case CANCELLED = 'cancelled';

    /**
     * The two below appear in no specification: chapter 04 lists the seven
     * above, and `refund` is a transaction *type* there, not a status. Neither
     * could be reached on the sandbox, which never settles a payment.
     *
     * They stay because a status is a response value a caller never chooses.
     * Responses hydrate with tryFrom, so dropping a case the gateway does send
     * would turn it into a silent null rather than an error.
     */
    case SUBMITTED_FOR_SETTLEMENT = 'submitted_for_settlement';

    case REFUND = 'refund';

    /**
     * @deprecated Nothing calls it, no specification describes a state machine,
     *     and its settled-to-refund transition is not one the gateway makes:
     *     a refund is a separate transaction of type refund.
     */
    public function canChange(TransactionStatus $newStatus): bool
    {
        if ($newStatus === self::NEW) {
            return false;
        }

        if ($this === self::SETTLED && $newStatus === self::REFUND) {
            return true;
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
