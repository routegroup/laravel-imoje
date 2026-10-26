<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentProfileDto;
use Routegroup\Imoje\Payment\Factories\Responses\ProfilesResponseDtoFactory;

/**
 * @property-read list<PaymentProfileDto> $paymentProfiles
 *
 * @method static ProfilesResponseDtoFactory factory($count = null, $state = [])
 */
class ProfilesResponseDto extends ResponseDto
{
    use HasFactory;

    public function __construct($incomingData)
    {
        parent::__construct($incomingData);

        $raw = $this->attributes;
        $declared = $raw['paymentProfiles'] ?? null;

        // The specification declares {paymentProfiles: [...]}, the sibling list
        // endpoint /services answers with a bare list of wrapped items, and the
        // sandbox has no profile to settle it with. Accept every one of those.
        $list = match (true) {
            is_array($declared) && array_is_list($declared) => $declared,
            array_is_list($raw) => $raw,
            default => [],
        };

        $this->attributes = ['paymentProfiles' => array_map(
            static fn (mixed $item): PaymentProfileDto => new PaymentProfileDto(
                is_array($item) ? ($item['paymentProfile'] ?? $item) : []
            ),
            $list,
        )];
    }

    protected static function newFactory(): ProfilesResponseDtoFactory
    {
        return ProfilesResponseDtoFactory::new();
    }
}
