<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Notifications\ReportNotificationDtoFactory;

/**
 * A notification announcing that a scheduled report is ready to download.
 *
 * These are delivered to the notification address configured for the report
 * itself, not to the shop's notification address, and the specification states
 * they carry no signature. Do not route them through
 * Validator::verifySignature(): it rejects unsigned payloads by design, and
 * accepting one would mean trusting an attacker-supplied download url.
 *
 * @property-read string|null $id
 * @property-read string|null $status
 * @property-read string|null $fileExt
 * @property-read int|null $created
 * @property-read int|null $modified
 * @property-read string|null $url
 *
 * @method static ReportNotificationDtoFactory factory($count = null, $state = [])
 */
class ReportNotificationDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    /**
     * status and fileExt are left as plain strings rather than enums: casting
     * through a backed enum would throw on any value ING adds later, which is
     * the problem tracked for the existing enum casts.
     */
    protected array $casts = [
        'created' => 'int',
        'modified' => 'int',
    ];

    protected static function newFactory(): ReportNotificationDtoFactory
    {
        return ReportNotificationDtoFactory::new();
    }
}
