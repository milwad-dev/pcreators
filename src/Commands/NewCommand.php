<?php

declare(strict_types=1);

namespace Pcreators\Commands;

use Pcreators\PackageDefinition;
use Pcreators\PackageGenerator;
use Pcreators\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

#[AsCommand(name: 'new', description: 'Scaffold a new PHP/Laravel package interactively.')]
final class NewCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'Directory to create the package in (defaults to ./<name>).'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        intro('pcreators — scaffold a PHP or Laravel package in seconds');

        // ---- Package type ----
        $type = select(
            label: 'What kind of package is this?',
            options: [
                'laravel' => 'Laravel package (service provider, config, auto-discovery)',
                'php'     => 'Plain PHP package (framework-agnostic library)',
            ],
            default: 'laravel',
        );
        $isLaravel = $type === 'laravel';

        // ---- Identity ----
        $vendor = text(
            label: 'Vendor name',
            placeholder: 'acme',
            required: true,
            hint: 'The org/handle part of the composer name, e.g. "acme" in acme/blog.',
            validate: fn (string $v) => $this->validateSlugPart($v, 'Vendor'),
        );

        $name = text(
            label: 'Package name',
            placeholder: 'blog-manager',
            required: true,
            hint: 'The project part, e.g. "blog-manager" in acme/blog-manager.',
            validate: fn (string $v) => $this->validateSlugPart($v, 'Package name'),
        );

        $defaultDescription = $isLaravel ? 'A great Laravel package.' : 'A great PHP package.';
        $description = text(
            label: 'Short description',
            placeholder: $defaultDescription,
            default: $defaultDescription,
        );

        // ---- Author ----
        $authorName = text(
            label: 'Author name',
            default: $this->guessGitConfig('user.name') ?? '',
            required: true,
        );

        $authorEmail = text(
            label: 'Author email',
            default: $this->guessGitConfig('user.email') ?? '',
            validate: fn (string $v) => ($v === '' || filter_var($v, FILTER_VALIDATE_EMAIL))
                ? null
                : 'Please enter a valid email address.',
        );

        // ---- Tooling ----
        $phpVersion = select(
            label: 'Minimum PHP version',
            options: ['8.5', '8.4', '8.3', '8.2', '8.1'],
            default: '8.2',
        );

        // ---- Feature selection (options depend on the package type) ----
        $options = [
            'tests' => $isLaravel
                ? 'Testing setup (Testbench + example tests)'
                : 'Testing setup (Pest/PHPUnit + example tests)',
            'ci'    => 'CI & quality tools (GitHub Actions, PHPStan, Pint)',
        ];

        if ($isLaravel) {
            $options += [
                'facade'    => 'Facade',
                'command'   => 'Artisan command',
                'migration' => 'Database migration',
                'routes'    => 'Routes file',
                'views'     => 'Blade views',
            ];
        }

        $selected = multiselect(
            label: 'Which extras should the package include?',
            options: $options,
            default: ['tests', 'ci'],
            hint: $isLaravel
                ? 'Space to toggle, Enter to confirm. Service provider + config are always included.'
                : 'Space to toggle, Enter to confirm. A main class, README and license are always included.',
        );

        $features = [];
        foreach (array_keys($options) as $key) {
            $features[$key] = in_array($key, $selected, true);
        }

        $testRunner = 'pest';
        if ($features['tests']) {
            $testRunner = select(
                label: 'Test runner',
                options: ['pest' => 'Pest', 'phpunit' => 'PHPUnit'],
                default: 'pest',
            );
        }

        $definition = new PackageDefinition(
            vendor: $vendor,
            name: $name,
            description: $description,
            authorName: $authorName,
            authorEmail: $authorEmail,
            phpVersion: $phpVersion,
            testRunner: $testRunner,
            features: $features,
            type: $type,
        );

        // ---- Confirmation ----
        $targetPath = $this->resolveTargetPath($input, $definition);

        note($this->summary($definition, $targetPath), 'Review');

        if (! confirm('Generate this package?', default: true)) {
            outro('Cancelled — nothing was written.');

            return Command::SUCCESS;
        }

        // ---- Generate ----
        $generator = new PackageGenerator(stubsPath: \dirname(__DIR__, 2) . '/stubs');

        try {
            $written = spin(
                fn () => $generator->generate($definition, $targetPath),
                'Generating package…',
            );
        } catch (Throwable $e) {
            outro('Failed: ' . $e->getMessage());

            return Command::FAILURE;
        }

        info(count($written) . ' files created in ' . $targetPath);

        note($this->nextSteps($definition, $targetPath), 'Next steps');

        outro('Done! Your package is ready. 🎉');

        return Command::SUCCESS;
    }

    private function resolveTargetPath(InputInterface $input, PackageDefinition $def): string
    {
        $path = $input->getArgument('path');

        if (is_string($path) && $path !== '') {
            return $this->absolute($path);
        }

        return $this->absolute(Str::slug($def->name));
    }

    private function absolute(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return getcwd() . '/' . ltrim($path, './');
    }

    private function summary(PackageDefinition $def, string $targetPath): string
    {
        $extras = array_keys(array_filter($def->features));
        $extrasLabel = $extras === [] ? 'none' : implode(', ', $extras);

        $lines = [
            'Package    : ' . $def->composerName(),
            'Type       : ' . ($def->isLaravel() ? 'Laravel' : 'PHP'),
            'Namespace  : ' . $def->namespace(),
        ];

        if ($def->isLaravel()) {
            $lines[] = 'Provider   : ' . $def->providerClass();
        }

        $lines[] = 'PHP        : ^' . $def->phpVersion;
        $lines[] = 'Tests      : ' . ($def->has('tests') ? $def->testRunner : 'none');
        $lines[] = 'Extras     : ' . $extrasLabel;
        $lines[] = 'Location   : ' . $targetPath;

        return implode("\n", $lines);
    }

    private function nextSteps(PackageDefinition $def, string $targetPath): string
    {
        $dir = basename($targetPath);
        $steps = ["cd {$dir}", 'composer install'];

        if ($def->has('tests')) {
            $steps[] = $def->usesPest() ? './vendor/bin/pest' : './vendor/bin/phpunit';
        }

        return implode("\n", $steps);
    }

    private function validateSlugPart(string $value, string $label): ?string
    {
        $slug = Str::slug($value);

        if ($slug === '') {
            return "{$label} must contain letters or numbers.";
        }

        return null;
    }

    private function guessGitConfig(string $key): ?string
    {
        $value = @shell_exec('git config ' . escapeshellarg($key) . ' 2>/dev/null');

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
