# Contributing

Thanks for your interest in improving **pcreators**! Contributions are welcome.

## Getting started

```bash
git clone https://github.com/milwad-dev/pcreators.git
cd pcreators
composer install
```

Run the tool locally:

```bash
php bin/pcreators
```

## Tests

Please make sure the test suite passes before opening a pull request:

```bash
composer test
```

New behaviour should come with tests. The suite lives in `tests/` and uses
[Pest](https://pestphp.com/).

## How it's organised

- `src/` — the CLI, the generator and its supporting classes.
- `stubs/` — the templates used to generate packages. Most changes to the
  generated output belong here.
- `tests/` — the Pest test suite.

Stub syntax:

- `{{ placeholder }}` — replaced from `PackageDefinition::replacements()`.
- `{{#feature}} … {{/feature}}` — kept only when that feature is enabled.
- `{{#laravel}}` / `{{#php}}` — kept depending on the package type.

## Pull requests

1. Fork the repository and create a feature branch.
2. Make your change, with tests.
3. Ensure `composer test` passes.
4. Open a pull request describing the change and the motivation.

## Reporting issues

Found a bug or have an idea? Please
[open an issue](https://github.com/milwad-dev/pcreators/issues) with as much
detail as you can (steps to reproduce, expected vs actual behaviour).
