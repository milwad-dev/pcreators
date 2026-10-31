<?php

declare(strict_types=1);

use Pcreators\PackageGenerator;

beforeEach(function () {
    $this->target = tempDir();
    $this->generator = new PackageGenerator(basePath('stubs'));
});

afterEach(function () {
    rrmdir($this->target);
});

/** Generate and return the list of written relative paths. */
function generate(PackageGenerator $generator, $definition, string $target): array
{
    return $generator->generate($definition, $target);
}

it('writes the always-included files for a bare package', function () {
    $written = generate($this->generator, makeDefinition(), $this->target);

    expect($written)->toContain(
        'composer.json',
        '.gitignore',
        '.gitattributes',
        '.editorconfig',
        'LICENSE.md',
        'README.md',
        'CHANGELOG.md',
        'src/BlogManagerServiceProvider.php',
        'src/BlogManager.php',
        'config/blog-manager.php',
    );
});

it('resolves placeholders in destination paths', function () {
    generate($this->generator, makeDefinition(), $this->target);

    expect($this->target . '/src/BlogManagerServiceProvider.php')->toBeFile()
        ->and($this->target . '/config/blog-manager.php')->toBeFile();
});

it('replaces placeholders inside file contents', function () {
    generate($this->generator, makeDefinition(), $this->target);

    $provider = file_get_contents($this->target . '/src/BlogManagerServiceProvider.php');

    expect($provider)
        ->toContain('namespace Acme\\BlogManager;')
        ->toContain('final class BlogManagerServiceProvider')
        ->not->toContain('{{');
});

it('does not emit extra files when no extras are selected', function () {
    generate($this->generator, makeDefinition(), $this->target);

    expect($this->target . '/src/Facades/BlogManager.php')->not->toBeFile()
        ->and($this->target . '/src/Commands/BlogManagerCommand.php')->not->toBeFile()
        ->and($this->target . '/routes/web.php')->not->toBeFile()
        ->and($this->target . '/tests')->not->toBeDirectory()
        ->and($this->target . '/.github')->not->toBeDirectory();
});

it('emits every extra when all features are selected', function () {
    $all = array_fill_keys(
        ['tests', 'ci', 'facade', 'command', 'migration', 'routes', 'views'],
        true
    );

    $written = generate($this->generator, makeDefinition($all), $this->target);

    expect($this->target . '/src/Facades/BlogManager.php')->toBeFile()
        ->and($this->target . '/src/Commands/BlogManagerCommand.php')->toBeFile()
        ->and($this->target . '/routes/web.php')->toBeFile()
        ->and($this->target . '/resources/views/example.blade.php')->toBeFile()
        ->and($this->target . '/phpstan.neon')->toBeFile()
        ->and($this->target . '/pint.json')->toBeFile()
        ->and($this->target . '/testbench.yaml')->toBeFile();

    // A timestamped migration lands under database/migrations.
    $migrations = glob($this->target . '/database/migrations/*_create_blog_manager_table.php');
    expect($migrations)->toHaveCount(1);

    // The test-running workflow ships alongside the suite.
    expect($written)->toContain('.github/workflows/run-tests.yml');
});

it('wires selected features into the service provider boot method', function () {
    $all = array_fill_keys(
        ['tests', 'ci', 'facade', 'command', 'migration', 'routes', 'views'],
        true
    );

    generate($this->generator, makeDefinition($all), $this->target);
    $provider = file_get_contents($this->target . '/src/BlogManagerServiceProvider.php');

    expect($provider)
        ->toContain('$this->loadMigrationsFrom(')
        ->toContain('$this->loadRoutesFrom(')
        ->toContain('$this->loadViewsFrom(')
        ->toContain('BlogManagerCommand::class')
        ->not->toContain('{{#');
});

it('leaves no conditional wiring in a bare service provider', function () {
    generate($this->generator, makeDefinition(), $this->target);
    $provider = file_get_contents($this->target . '/src/BlogManagerServiceProvider.php');

    expect($provider)
        ->not->toContain('loadMigrationsFrom')
        ->not->toContain('loadRoutesFrom')
        ->not->toContain('loadViewsFrom')
        ->not->toContain('{{#')
        ->not->toContain('{{/');
});

it('generates pest test files when the pest runner is chosen', function () {
    generate($this->generator, makeDefinition(['tests' => true], 'pest'), $this->target);

    expect($this->target . '/tests/Pest.php')->toBeFile()
        ->and($this->target . '/tests/Feature/ExampleTest.php')->toBeFile()
        ->and($this->target . '/phpunit.xml.dist')->toBeFile();
});

it('generates a phpunit test class when the phpunit runner is chosen', function () {
    generate($this->generator, makeDefinition(['tests' => true], 'phpunit'), $this->target);

    expect($this->target . '/tests/Pest.php')->not->toBeFile();

    $test = file_get_contents($this->target . '/tests/Feature/ExampleTest.php');
    expect($test)->toContain('final class ExampleTest extends TestCase');
});

it('produces only lint-clean, syntactically valid php', function () {
    $all = array_fill_keys(
        ['tests', 'ci', 'facade', 'command', 'migration', 'routes', 'views'],
        true
    );

    generate($this->generator, makeDefinition($all), $this->target);

    $phpFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->target, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($phpFiles as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $out, $code);
        expect($code)->toBe(0, "Syntax error in {$file->getPathname()}: " . implode("\n", $out));
    }
});

it('refuses to overwrite a non-empty target directory', function () {
    mkdir($this->target, 0777, true);
    file_put_contents($this->target . '/existing.txt', 'keep me');

    expect(fn () => generate($this->generator, makeDefinition(), $this->target))
        ->toThrow(RuntimeException::class, 'not empty');
});

it('generates into an existing but empty directory', function () {
    mkdir($this->target, 0777, true);

    $written = generate($this->generator, makeDefinition(), $this->target);

    expect($written)->not->toBeEmpty()
        ->and($this->target . '/composer.json')->toBeFile();
});

it('omits the service provider and config for a plain php package', function () {
    generate($this->generator, makeDefinition(type: 'php'), $this->target);

    expect($this->target . '/src/BlogManager.php')->toBeFile()
        ->and($this->target . '/src/BlogManagerServiceProvider.php')->not->toBeFile()
        ->and($this->target . '/config/blog-manager.php')->not->toBeFile();
});

it('ignores laravel-only extras for a php package even when requested', function () {
    $laravelExtras = array_fill_keys(['facade', 'command', 'migration', 'routes', 'views'], true);

    generate($this->generator, makeDefinition($laravelExtras, type: 'php'), $this->target);

    expect($this->target . '/src/Facades/BlogManager.php')->not->toBeFile()
        ->and($this->target . '/src/Commands/BlogManagerCommand.php')->not->toBeFile()
        ->and($this->target . '/routes/web.php')->not->toBeFile()
        ->and($this->target . '/resources/views/example.blade.php')->not->toBeFile()
        ->and($this->target . '/database')->not->toBeDirectory();
});

it('generates a testbench-free pest suite for a php package', function () {
    generate($this->generator, makeDefinition(['tests' => true], 'pest', type: 'php'), $this->target);

    expect($this->target . '/testbench.yaml')->not->toBeFile()
        ->and($this->target . '/tests/TestCase.php')->not->toBeFile()
        ->and($this->target . '/tests/Feature/ExampleTest.php')->toBeFile();

    $pest = file_get_contents($this->target . '/tests/Pest.php');
    expect($pest)->not->toContain('TestCase');

    $example = file_get_contents($this->target . '/tests/Feature/ExampleTest.php');
    expect($example)->not->toContain('config(');
});

it('extends the native phpunit test case for a php phpunit package', function () {
    generate($this->generator, makeDefinition(['tests' => true], 'phpunit', type: 'php'), $this->target);

    $example = file_get_contents($this->target . '/tests/Feature/ExampleTest.php');

    expect($example)->toContain('use PHPUnit\\Framework\\TestCase;')
        ->toContain('final class ExampleTest extends TestCase')
        ->not->toContain('config(')
        ->not->toContain('{{');
});

it('omits the larastan include from phpstan config for a php package', function () {
    generate($this->generator, makeDefinition(['ci' => true], type: 'php'), $this->target);

    $neon = file_get_contents($this->target . '/phpstan.neon');

    expect($neon)->not->toContain('larastan')
        ->toContain('level: 5');
});

it('produces lint-clean php for a php package with tests and ci', function () {
    generate($this->generator, makeDefinition(['tests' => true, 'ci' => true], 'phpunit', type: 'php'), $this->target);

    $phpFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->target, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($phpFiles as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $out, $code);
        expect($code)->toBe(0, "Syntax error in {$file->getPathname()}: " . implode("\n", $out));
    }
});
