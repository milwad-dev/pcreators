<?php

declare(strict_types=1);

namespace Pcreators;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class PackageGenerator
{
    private Filesystem $files;

    /** @var list<string> Paths written, relative to the package root. */
    private array $written = [];

    private PackageDefinition $definition;

    public function __construct(
        private readonly string $stubsPath,
    ) {
        $this->files = new Filesystem();
    }

    /**
     * @return list<string> the files that were created, for reporting
     */
    public function generate(PackageDefinition $def, string $targetPath): array
    {
        if ($this->files->exists($targetPath) && ! $this->isEmptyDir($targetPath)) {
            throw new RuntimeException("Target directory is not empty: {$targetPath}");
        }

        $this->written = [];
        $this->definition = $def;
        $replacements = $def->replacements();

        // composer.json is built programmatically so it only references the
        // dependencies, scripts and aliases the selected features need.
        $this->write('composer.json', (new ComposerManifest($def))->toJson(), $targetPath);

        foreach ($this->fileMap($def) as $stub => $destination) {
            $this->render($stub, $destination, $replacements, $targetPath);
        }

        sort($this->written);

        return $this->written;
    }

    /**
     * Build the stub => destination map for this definition.
     *
     * @return array<string, string>
     */
    private function fileMap(PackageDefinition $def): array
    {
        $map = [
            // --- Always included, for both PHP and Laravel packages ---
            // (composer.json is generated separately, see generate())
            'gitignore.stub'                 => '.gitignore',
            'gitattributes.stub'             => '.gitattributes',
            'editorconfig.stub'              => '.editorconfig',
            'LICENSE.md.stub'                => 'LICENSE.md',
            'README.md.stub'                 => 'README.md',
            'CHANGELOG.md.stub'              => 'CHANGELOG.md',
            'src/MainClass.php.stub'         => 'src/{{ main_class }}.php',
        ];

        // --- Laravel package essentials ---
        if ($def->isLaravel()) {
            $map['src/ServiceProvider.php.stub'] = 'src/{{ provider_class }}.php';
            $map['config/config.php.stub'] = 'config/{{ config_key }}.php';
        }

        // --- Testing setup ---
        if ($def->has('tests')) {
            $map['phpunit.xml.stub'] = 'phpunit.xml.dist';

            // A Laravel suite boots the app through Testbench.
            if ($def->isLaravel()) {
                $map['testbench.yaml.stub'] = 'testbench.yaml';
                $map['tests/TestCase.php.stub'] = 'tests/TestCase.php';
            }

            if ($def->usesPest()) {
                $map['tests/Pest.php.stub'] = 'tests/Pest.php';
                $map['tests/pest/ExampleTest.php.stub'] = 'tests/Feature/ExampleTest.php';
            } else {
                $map['tests/phpunit/ExampleTest.php.stub'] = 'tests/Feature/ExampleTest.php';
            }

            // The test-running workflow belongs with the test suite it runs.
            if ($def->has('ci')) {
                $map['github/workflows/run-tests.yml.stub'] = '.github/workflows/run-tests.yml';
            }
        }

        // --- CI & quality tooling ---
        if ($def->has('ci')) {
            $map['github/workflows/static-analysis.yml.stub'] = '.github/workflows/static-analysis.yml';
            $map['github/workflows/lint.yml.stub'] = '.github/workflows/lint.yml';
            $map['phpstan.neon.stub'] = 'phpstan.neon';
            $map['pint.json.stub'] = 'pint.json';
        }

        // --- Laravel-only extras ---
        if ($def->isLaravel()) {
            if ($def->has('facade')) {
                $map['src/Facade.php.stub'] = 'src/Facades/{{ main_class }}.php';
            }

            if ($def->has('command')) {
                $map['src/Command.php.stub'] = 'src/Commands/{{ main_class }}Command.php';
            }

            if ($def->has('migration')) {
                $stamp = date('Y_m_d_His');
                $map['database/migration.php.stub'] = "database/migrations/{$stamp}_create_{{ table_name }}_table.php";
            }

            if ($def->has('routes')) {
                $map['routes/web.php.stub'] = 'routes/web.php';
            }

            if ($def->has('views')) {
                $map['resources/views/example.blade.php.stub'] = 'resources/views/example.blade.php';
            }
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function render(string $stub, string $destination, array $replacements, string $targetPath): void
    {
        $stubFile = $this->stubsPath . '/' . $stub;

        if (! $this->files->exists($stubFile)) {
            throw new RuntimeException("Missing stub: {$stub}");
        }

        $contents = (string) file_get_contents($stubFile);
        $contents = $this->applyConditionals($contents, $this->definition);
        $contents = strtr($contents, $replacements);

        // The destination itself may contain placeholders.
        $relative = strtr($destination, $replacements);

        $this->write($relative, $contents, $targetPath);
    }

    /**
     * Resolve `{{#name}} … {{/name}}` blocks, keeping the body only when the
     * condition holds. `name` is a selected feature, or the pseudo-conditions
     * `laravel` / `php` matching the package type. Leftover blank lines from
     * stripped blocks are collapsed.
     */
    private function applyConditionals(string $contents, PackageDefinition $def): string
    {
        $contents = (string) preg_replace_callback(
            '/\{\{#(\w+)\}\}\n?(.*?)\{\{\/\1\}\}\n?/s',
            fn (array $m): string => $this->conditionHolds($def, $m[1]) ? $m[2] : '',
            $contents
        );

        // Collapse 3+ consecutive newlines left by removed blocks down to 2,
        // then drop any blank line that ended up right before a closing brace.
        $contents = (string) preg_replace("/\n{3,}/", "\n\n", $contents);

        return (string) preg_replace("/\n[ \t]*\n([ \t]*\})/", "\n$1", $contents);
    }

    private function conditionHolds(PackageDefinition $def, string $name): bool
    {
        return match ($name) {
            'laravel' => $def->isLaravel(),
            'php' => ! $def->isLaravel(),
            default => $def->has($name),
        };
    }

    private function write(string $relative, string $contents, string $targetPath): void
    {
        $fullPath = rtrim($targetPath, '/') . '/' . $relative;

        $this->files->mkdir(\dirname($fullPath));
        $this->files->dumpFile($fullPath, $contents);

        $this->written[] = $relative;
    }

    private function isEmptyDir(string $path): bool
    {
        if (! is_dir($path)) {
            return false;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                return false;
            }
        }

        return true;
    }
}
