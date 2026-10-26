<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\BrowserDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class BrowserDtoFactory extends Factory
{
    protected $model = BrowserDto::class;

    /** The example from the Google Pay and Apple Pay chapter. */
    public function definition(): array
    {
        return [
            'ip' => '127.0.0.1',
            'language' => 'pl-PL',
            'jsEnabled' => true,
            'timezoneOffset' => 100,
            'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36',
            'accept' => 'application/json, text/javascript, */*; q=0.01',
            'javaEnabled' => false,
            'screenColorDepth' => 24,
            'screenHeight' => 1080,
            'screenWidth' => 2560,
        ];
    }
}
