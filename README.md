# laravel-imoje

[![Latest Version on Packagist](https://img.shields.io/packagist/v/routegroup/laravel-imoje.svg?style=flat-square)](https://packagist.org/packages/routegroup/laravel-imoje)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/routegroup/laravel-imoje/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/routegroup/laravel-imoje/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/routegroup/laravel-imoje/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/routegroup/laravel-imoje/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/routegroup/laravel-imoje.svg?style=flat-square)](https://packagist.org/packages/routegroup/laravel-imoje)

## Introduction

This package is an integration of [imoje payments](https://www.imoje.pl/) with typed objects, which will help you integrate payments quickly. 

## Installation

You can install the package via composer:

```bash
composer require routegroup/laravel-imoje
```

Add to your `config/services.php`

```php
'imoje' => [
    'merchant_id' => env('IMOJE_MERCHANT_ID'),
    'service_id' => env('IMOJE_SERVICE_ID'),
    'service_key' => env('IMOJE_SERVICE_KEY'),
    'api_key' => env('IMOJE_API_KEY'),
    'env' => env('IMOJE_ENV'),
],
```

Add to your `.env`

```dotenv
IMOJE_MERCHANT_ID=
IMOJE_SERVICE_ID=
IMOJE_SERVICE_KEY=
IMOJE_API_KEY=
IMOJE_ENV=
```

## Usage

- [Paywall](docs/paywall.md) 
- [API](docs/api.md) 
- [Notifications](docs/notifications.md)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Kajetan Nobel](https://github.com/kajetan-nobel)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
