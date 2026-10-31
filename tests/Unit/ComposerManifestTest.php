<?php

declare(strict_types=1);

use Pcreators\ComposerManifest;

/**
 * @return array<string, mixed>
 */
function manifest(array $features = [], string $runner = 'pest', string $type = 'laravel'): array
{
    $json = (new ComposerManifest(makeDefinition($features, $runner, type: $type)))->toJson();

    return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
}

it('produces valid, prettified json ending in a newline', function () {
    $json = (new ComposerManifest(makeDefinition()))->toJson();

    expect($json)->toEndWith("}\n")
        ->and(json_decode($json, true))->toBeArray();
});

it('always sets name, autoloading and the service provider', function () {
    $data = manifest();

    expect($data['name'])->toBe('acme/blog-manager')
        ->and($data['autoload']['psr-4'])->toHaveKey('Acme\\BlogManager\\', 'src/')
        ->and($data['extra']['laravel']['providers'])
        ->toContain('Acme\\BlogManager\\BlogManagerServiceProvider');
});

it('omits pest, phpstan, pint and the facade alias with no extras', function () {
    $data = manifest();

    expect($data['require-dev'])->not->toHaveKey('pestphp/pest')
        ->and($data['require-dev'])->not->toHaveKey('larastan/larastan')
        ->and($data)->not->toHaveKey('scripts')
        ->and($data['extra']['laravel'])->not->toHaveKey('aliases')
        ->and($data['config'])->not->toHaveKey('allow-plugins');
});

it('adds pest dev deps and the test script when pest tests are enabled', function () {
    $data = manifest(['tests' => true], 'pest');

    expect($data['require-dev'])->toHaveKey('pestphp/pest')
        ->and($data['scripts']['test'])->toBe('vendor/bin/pest')
        ->and($data['config']['allow-plugins'])->toHaveKey('pestphp/pest-plugin');
});

it('uses phpunit dev deps when phpunit is chosen', function () {
    $data = manifest(['tests' => true], 'phpunit');

    expect($data['require-dev'])->toHaveKey('phpunit/phpunit')
        ->and($data['require-dev'])->not->toHaveKey('pestphp/pest')
        ->and($data['scripts']['test'])->toBe('vendor/bin/phpunit')
        ->and($data['config'])->not->toHaveKey('allow-plugins');
});

it('adds analysis tooling and scripts when ci is enabled', function () {
    $data = manifest(['ci' => true]);

    expect($data['require-dev'])->toHaveKey('larastan/larastan')
        ->and($data['require-dev'])->toHaveKey('laravel/pint')
        ->and($data['scripts']['analyse'])->toBe('vendor/bin/phpstan analyse')
        ->and($data['scripts']['format'])->toBe('vendor/bin/pint');
});

it('registers the facade alias only when the facade extra is selected', function () {
    $data = manifest(['facade' => true]);

    expect($data['extra']['laravel']['aliases'])
        ->toHaveKey('BlogManager', 'Acme\\BlogManager\\Facades\\BlogManager');
});

it('omits illuminate deps and the laravel extra for a plain php package', function () {
    $data = manifest(type: 'php');

    expect($data['require'])->not->toHaveKey('illuminate/support')
        ->and($data['require'])->not->toHaveKey('illuminate/contracts')
        ->and($data['require'])->toHaveKey('php')
        ->and($data)->not->toHaveKey('extra')
        ->and($data['keywords'])->toContain('php')
        ->and($data['keywords'])->not->toContain('laravel');
});

it('uses plain phpstan and no testbench for a php package with ci and tests', function () {
    $data = manifest(['tests' => true, 'ci' => true], 'pest', 'php');

    expect($data['require-dev'])->toHaveKey('phpstan/phpstan')
        ->and($data['require-dev'])->not->toHaveKey('larastan/larastan')
        ->and($data['require-dev'])->not->toHaveKey('orchestra/testbench')
        ->and($data['require-dev'])->not->toHaveKey('pestphp/pest-plugin-laravel')
        ->and($data['require-dev'])->toHaveKey('pestphp/pest');
});

it('omits require-dev entirely for a bare php package', function () {
    $data = manifest(type: 'php');

    expect($data)->not->toHaveKey('require-dev');
});
