<?php

declare(strict_types=1);

use Pcreators\Support\Str;

it('studly-cases hyphen, underscore and space separated words', function (string $input, string $expected) {
    expect(Str::studly($input))->toBe($expected);
})->with([
    ['blog-manager', 'BlogManager'],
    ['blog_manager', 'BlogManager'],
    ['Blog Manager', 'BlogManager'],
    ['blog', 'Blog'],
    ['API Client', 'APIClient'],
]);

it('slugs camelCase, spaces and symbols into kebab-case', function (string $input, string $expected) {
    expect(Str::slug($input))->toBe($expected);
})->with([
    ['BlogManager', 'blog-manager'],
    ['blog manager', 'blog-manager'],
    ['BlogManager Pro!', 'blog-manager-pro'],
    ['acme', 'acme'],
    ['  spaced  ', 'spaced'],
]);

it('snake-cases values', function () {
    expect(Str::snake('BlogManager'))->toBe('blog_manager')
        ->and(Str::snake('blog-manager'))->toBe('blog_manager');
});

it('titles values', function () {
    expect(Str::title('blog-manager'))->toBe('Blog Manager')
        ->and(Str::title('blog_manager'))->toBe('Blog Manager');
});

it('kebab is an alias of slug', function () {
    expect(Str::kebab('BlogManager'))->toBe(Str::slug('BlogManager'));
});
