<?php

declare(strict_types=1);

use Pcreators\PackageDefinition;

/*
|--------------------------------------------------------------------------
| Test helpers
|--------------------------------------------------------------------------
*/

/**
 * Absolute path to the project root (one level above tests/).
 */
function basePath(string $path = ''): string
{
    $root = dirname(__DIR__);

    return $path === '' ? $root : $root . '/' . ltrim($path, '/');
}

/**
 * A unique, empty scratch directory inside the system temp dir.
 */
function tempDir(): string
{
    $dir = sys_get_temp_dir() . '/pcreators-test-' . bin2hex(random_bytes(6));

    return $dir;
}

/**
 * Recursively delete a directory.
 */
function rrmdir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}

/**
 * Build a PackageDefinition with sensible defaults, overridable per test.
 *
 * @param  array<string, bool>  $features
 */
function makeDefinition(array $features = [], string $testRunner = 'pest', string $name = 'blog-manager', string $type = 'laravel'): PackageDefinition
{
    $defaults = [
        'tests' => false, 'ci' => false, 'facade' => false,
        'command' => false, 'migration' => false, 'routes' => false, 'views' => false,
    ];

    return new PackageDefinition(
        vendor: 'acme',
        name: $name,
        description: 'A great Laravel package.',
        authorName: 'Jane Dev',
        authorEmail: 'jane@example.com',
        phpVersion: '8.2',
        testRunner: $testRunner,
        features: array_merge($defaults, $features),
        type: $type,
    );
}
