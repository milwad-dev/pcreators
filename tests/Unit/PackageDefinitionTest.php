<?php

declare(strict_types=1);

it('derives composer name, namespace and class names', function () {
    $def = makeDefinition(name: 'blog-manager');

    expect($def->composerName())->toBe('acme/blog-manager')
        ->and($def->vendorStudly())->toBe('Acme')
        ->and($def->nameStudly())->toBe('BlogManager')
        ->and($def->namespace())->toBe('Acme\\BlogManager')
        ->and($def->providerClass())->toBe('BlogManagerServiceProvider')
        ->and($def->mainClass())->toBe('BlogManager')
        ->and($def->configKey())->toBe('blog-manager')
        ->and($def->tableName())->toBe('blog_manager')
        ->and($def->title())->toBe('Blog Manager');
});

it('escapes the namespace for composer.json', function () {
    $def = makeDefinition();

    expect($def->namespaceEscaped())->toBe('Acme\\\\BlogManager');
});

it('reports selected features via has()', function () {
    $def = makeDefinition(['facade' => true, 'command' => false]);

    expect($def->has('facade'))->toBeTrue()
        ->and($def->has('command'))->toBeFalse()
        ->and($def->has('nonexistent'))->toBeFalse();
});

it('knows which test runner is used', function () {
    expect(makeDefinition(testRunner: 'pest')->usesPest())->toBeTrue()
        ->and(makeDefinition(testRunner: 'phpunit')->usesPest())->toBeFalse();
});

it('exposes a full replacement map', function () {
    $def = makeDefinition();
    $replacements = $def->replacements();

    expect($replacements)
        ->toHaveKey('{{ namespace }}', 'Acme\\BlogManager')
        ->toHaveKey('{{ composer_name }}', 'acme/blog-manager')
        ->toHaveKey('{{ provider_class }}', 'BlogManagerServiceProvider')
        ->toHaveKey('{{ year }}', date('Y'));
});
