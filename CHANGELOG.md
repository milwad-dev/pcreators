# Changelog

All notable changes to `pcreators` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Interactive CLI wizard to scaffold PHP and Laravel packages.
- Package type selection (plain PHP library or Laravel package).
- Optional extras: tests (Pest/PHPUnit), CI & quality tooling (GitHub Actions,
  PHPStan/Larastan, Pint), facade, Artisan command, migration, routes, views.
- Feature-aware `composer.json` generation.
- Pest test suite covering the generator end to end.
- PHP 8.5 support (wizard option and CI matrix).
- Symfony 8 compatibility (`symfony/console` and `symfony/filesystem` now allow `^8.0`).
