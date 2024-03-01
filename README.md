# Setup Laravel Application with one command

[![Latest Version on Packagist](https://img.shields.io/packagist/v/itutu-media/laravel-installer.svg?style=flat-square)](https://packagist.org/packages/itutu-media/laravel-installer)
[![PHPStan](https://github.com/itutu-media/laravel-installer/actions/workflows/phpstan.yml/badge.svg)](https://github.com/itutu-media/laravel-installer/actions/workflows/phpstan.yml)
[![Fix PHP code style issues](https://github.com/itutu-media/laravel-installer/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/itutu-media/laravel-installer/actions/workflows/fix-php-code-style-issues.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/itutu-media/laravel-installer.svg?style=flat-square)](https://packagist.org/packages/itutu-media/laravel-installer)

## Installation

You can install the package via composer:

```bash
composer require itutu-media/laravel-installer
```

## Usage

```bash
php artisan app:install
```

### Command Options
The `app:install` command supports the following options:
> `--set-env` option will set the environment variables in the `.env` file.
> `--modules` option will install application as a modular application.
> `--force` option will override existing files.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [ITUTU Media](https://github.com/itutu-media)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
