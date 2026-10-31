<?php

declare(strict_types=1);

namespace Pcreators;

final readonly class ComposerManifest
{
    public function __construct(
        private PackageDefinition $def,
    ) {}

    public function toJson(): string
    {
        $def = $this->def;

        $manifest = [
            'name' => $def->composerName(),
            'description' => $def->description,
            'keywords' => array_values(array_unique([
                $def->vendor,
                $def->isLaravel() ? 'laravel' : 'php',
                $def->name,
            ])),
            'homepage' => "https://github.com/{$def->vendor}/{$def->name}",
            'license' => 'MIT',
            'authors' => [
                array_filter([
                    'name' => $def->authorName,
                    'email' => $def->authorEmail !== '' ? $def->authorEmail : null,
                    'role' => 'Developer',
                ]),
            ],
            'require' => $this->require(),
            'require-dev' => $this->devDependencies(),
            'autoload' => [
                'psr-4' => [
                    $def->namespace() . '\\' => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    $def->namespace() . '\\Tests\\' => 'tests/',
                ],
            ],
            'scripts' => $this->scripts(),
            'config' => $this->config(),
            'extra' => $this->extra(),
            'minimum-stability' => 'stable',
            'prefer-stable' => true,
        ];

        foreach (['require-dev', 'scripts', 'extra'] as $optional) {
            if ($manifest[$optional] === []) {
                unset($manifest[$optional]);
            }
        }

        $json = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return $json . "\n";
    }

    /**
     * @return array<string, string>
     */
    private function require(): array
    {
        $require = ['php' => "^{$this->def->phpVersion}"];

        if ($this->def->isLaravel()) {
            $require['illuminate/contracts'] = '^10.0 || ^11.0 || ^12.0';
            $require['illuminate/support'] = '^10.0 || ^11.0 || ^12.0';
        }

        return $require;
    }

    /**
     * @return array<string, string>
     */
    private function devDependencies(): array
    {
        $def = $this->def;
        $dev = [];

        if ($def->isLaravel()) {
            $dev['orchestra/testbench'] = '^8.0 || ^9.0 || ^10.0';
        }

        if ($def->has('tests')) {
            if ($def->usesPest()) {
                $dev['pestphp/pest'] = '^2.0 || ^3.0';

                if ($def->isLaravel()) {
                    $dev['pestphp/pest-plugin-laravel'] = '^2.0 || ^3.0';
                }
            } else {
                $dev['phpunit/phpunit'] = '^10.0 || ^11.0';
            }
        }

        if ($def->has('ci')) {
            // Larastan extends PHPStan with Laravel-aware rules; a plain PHP
            // package just needs PHPStan itself.
            $dev[$def->isLaravel() ? 'larastan/larastan' : 'phpstan/phpstan'] =
                $def->isLaravel() ? '^2.0 || ^3.0' : '^1.11 || ^2.0';
            $dev['laravel/pint'] = '^1.0';
        }

        ksort($dev);

        return $dev;
    }

    /**
     * Laravel package auto-discovery (empty for a plain PHP package).
     *
     * @return array<string, mixed>
     */
    private function extra(): array
    {
        $def = $this->def;

        if (! $def->isLaravel()) {
            return [];
        }

        return [
            'laravel' => array_filter([
                'providers' => [$def->namespace() . '\\' . $def->providerClass()],
                'aliases' => $def->has('facade')
                    ? [$def->mainClass() => $def->namespace() . '\\Facades\\' . $def->mainClass()]
                    : null,
            ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function scripts(): array
    {
        $def = $this->def;
        $scripts = [];

        if ($def->has('tests')) {
            $scripts['test'] = $def->usesPest() ? 'vendor/bin/pest' : 'vendor/bin/phpunit';
            $scripts['test-coverage'] = $def->usesPest()
                ? 'vendor/bin/pest --coverage'
                : 'vendor/bin/phpunit --coverage-html coverage';
        }

        if ($def->has('ci')) {
            $scripts['analyse'] = 'vendor/bin/phpstan analyse';
            $scripts['format'] = 'vendor/bin/pint';
        }

        return $scripts;
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $config = ['sort-packages' => true];

        if ($this->def->has('tests') && $this->def->usesPest()) {
            $config['allow-plugins'] = ['pestphp/pest-plugin' => true];
        }

        return $config;
    }
}
