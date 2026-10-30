<div align="center">
    <h1>Guardian</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/refactor-circus/guardian"><img src="https://img.shields.io/packagist/v/refactor-circus/guardian.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/refactor-circus/guardian"><img src="https://img.shields.io/packagist/php-v/refactor-circus/guardian.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/refactor-circus/guardian"><img src="https://badge.laravel.cloud/badge/refactor-circus/guardian?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/refactor-circus/guardian/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/refactor-circus/guardian/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/refactor-circus/guardian"><img src="https://img.shields.io/packagist/dt/refactor-circus/guardian.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Auth and security for the Refactor Circus suite, starting with SSO and SCIM extracted from Roster

## Installation

You can install the package via Composer:

```bash
composer require refactor-circus/guardian
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="guardian"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="guardian-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="guardian-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="guardian-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="guardian-lang"
```

## Usage

<!-- Add a basic usage example here. -->

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Guardian! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Jay Fletcher](https://github.com/refactor-circus)
- [All Contributors](../../contributors)

## License

Guardian is open-sourced software licensed under the [MIT license](LICENSE.md).
