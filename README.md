# PCreators

<img src="https://banners.beyondco.de/PCreators.png?theme=dark&packageManager=composer+require&packageName=milwad%2Fpcreators&pattern=wiggle&style=style_2&description=create+the+base+structure+of+a+PHP+or+Laravel+package+in+one+command&md=1&showWatermark=1&fontSize=100px&images=https%3A%2F%2Fwww.php.net%2Fimages%2Flogos%2Fnew-php-logo.svg" alt="pcreators-banner">

[![Latest Version on Packagist](https://img.shields.io/packagist/v/milwad/pcreators.svg?style=flat-square)](https://packagist.org/packages/milwad/pcreators)
[![Tests](https://img.shields.io/github/actions/workflow/status/milwad-dev/pcreators/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/milwad-dev/pcreators/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/milwad/pcreators.svg?style=flat-square)](https://packagist.org/packages/milwad/pcreators)
[![PHP Version](https://img.shields.io/packagist/php-v/milwad/pcreators?style=flat-square)](https://packagist.org/packages/milwad/pcreators)
[![License](https://img.shields.io/packagist/l/milwad/pcreators.svg?style=flat-square)](LICENSE.md)

An interactive CLI that scaffolds a **production-ready PHP/Laravel package** in seconds. Answer a few prompts and get a complete, test-passing, CI-ready package skeleton.



```
$ pcreators
 ┌ pcreators — scaffold a Laravel package in seconds ────────────┐

 ┌ Vendor name ─────────────────────────────────────────────────┐
 │ acme                                                          │
 └──────────────────────────────────────────────────────────────┘
 ...
```

## Requirements

- PHP 8.2+
- Composer

## Installation

Install globally so `pcreators` is available everywhere:

```bash
composer global require milwad/pcreators
```

Make sure your global Composer `bin` directory is on your `PATH`
(`~/.composer/vendor/bin` or `~/.config/composer/vendor/bin`).

Or run it from a local clone:

```bash
git clone https://github.com/milwad-dev/pcreators.git
cd pcreators
composer install
php bin/pcreators
```

## Usage

```bash
# Interactive — creates ./<package-name>
pcreators

# Generate into a specific directory
pcreators packages/my-package
```

The wizard asks for:

- **Vendor** and **package name** (e.g. `acme` / `blog-manager` → `acme/blog-manager`)
- **Description**, **author name/email** (pre-filled from your git config)
- **Minimum PHP version**
- **Extras** to include (multi-select)
- **Test runner** (Pest or PHPUnit) when tests are enabled

## What it generates

Always included:

- `composer.json` with PSR-4 autoloading and Laravel auto-discovery
- A **service provider** (publishable config, feature wiring)
- A **config file**, main class, README, LICENSE, CHANGELOG
- `.gitignore`, `.gitattributes`, `.editorconfig`

Optional (toggle during generation):

| Extra | Produces |
| --- | --- |
| **Testing setup** | Orchestra Testbench, a `TestCase`, example tests, `phpunit.xml.dist` (Pest **or** PHPUnit) |
| **CI & quality tools** | GitHub Actions (tests, PHPStan/Larastan, Pint), `phpstan.neon`, `pint.json` |
| **Facade** | `src/Facades/<Name>.php` + registered alias |
| **Artisan command** | `src/Commands/<Name>Command.php`, auto-registered |
| **Migration** | `database/migrations/..._create_<table>_table.php`, auto-loaded |
| **Routes** | `routes/web.php`, auto-loaded |
| **Blade views** | `resources/views/example.blade.php`, namespaced |

The service provider only wires up the features you select, so there are no
dangling references, and `composer.json` only pulls the dev dependencies you
actually need. A freshly generated package passes `pest`, `phpstan` and
`pint` out of the box.

## Testing

The tool itself is covered by a Pest suite:

```bash
composer test
```

It exercises the string helpers, the derived `PackageDefinition` values, the
feature-aware `composer.json` builder, and the generator end to end — including
that every generated package is syntactically valid PHP.

## How it works

- [`bin/pcreators`](bin/pcreators) — console entry point (Symfony Console).
- [`src/Commands/NewCommand.php`](src/Commands/NewCommand.php) — the interactive wizard (Laravel Prompts).
- [`src/PackageDefinition.php`](src/PackageDefinition.php) — the user's answers plus every derived value (namespace, class names, slugs).
- [`src/ComposerManifest.php`](src/ComposerManifest.php) — builds the generated `composer.json` from the selected features.
- [`src/PackageGenerator.php`](src/PackageGenerator.php) — maps stubs to destinations, resolves `{{#feature}}…{{/feature}}` blocks, and writes files.
- [`stubs/`](stubs) — the templates. Edit these to change what gets generated.

### Stub syntax

- `{{ placeholder }}` — replaced from `PackageDefinition::replacements()`.
- `{{#feature}} … {{/feature}}` — kept only when that feature is enabled.

Placeholders also work in destination paths, so `src/ServiceProvider.php.stub`
lands at `src/<Name>ServiceProvider.php`.

## License

* This package is created and modified by <a href="https://github.com/milwad-dev" target="_blank">Milwad Khosravi</a> for Laravel/PHP has been released under the MIT License.

## Contributing

This project exists thanks to all the people who contribute. [CONTRIBUTING](https://github.com/milwad-dev/pcreators/graphs/contributors)

<a href="https://github.com/milwad-dev/pcreators/graphs/contributors"><img src="https://opencollective.com/pcreators/contributors.svg?width=890&button=false" /></a>

## Security

If you've found a security bug, please email [milwad.dev@gmail.com](mailto:milwad.dev@gmail.com) instead of using the issue tracker.

## Star History

[![Star History Chart](https://star-history.dera.page/svg?repos=milwad-dev/pcreators&type=Date)](https://star-history.dera.page/#milwad-dev/pcreators&Date)

