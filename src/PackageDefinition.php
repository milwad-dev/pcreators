<?php

declare(strict_types=1);

namespace Pcreators;

use Pcreators\Support\Str;

final readonly class PackageDefinition
{
    /**
     * @param  array<string, bool>  $features
     */
    public function __construct(
        public string $vendor,
        public string $name,
        public string $description,
        public string $authorName,
        public string $authorEmail,
        public string $phpVersion,
        public string $testRunner,
        public array  $features = [],
        public string $type = 'laravel',
    ) {
    }

    public function has(string $feature): bool
    {
        return (bool) ($this->features[$feature] ?? false);
    }

    public function isLaravel(): bool
    {
        return $this->type === 'laravel';
    }

    public function usesPest(): bool
    {
        return $this->testRunner === 'pest';
    }

    /** "acme/blog-manager" */
    public function composerName(): string
    {
        return Str::slug($this->vendor) . '/' . Str::slug($this->name);
    }

    /** "Acme" */
    public function vendorStudly(): string
    {
        return Str::studly($this->vendor);
    }

    /** "BlogManager" */
    public function nameStudly(): string
    {
        return Str::studly($this->name);
    }

    /** "Acme\\BlogManager" */
    public function namespace(): string
    {
        return $this->vendorStudly() . '\\' . $this->nameStudly();
    }

    /** Escaped for use inside composer.json's double-quoted PSR-4 key. */
    public function namespaceEscaped(): string
    {
        return str_replace('\\', '\\\\', $this->namespace());
    }

    /** "BlogManagerServiceProvider" */
    public function providerClass(): string
    {
        return $this->nameStudly() . 'ServiceProvider';
    }

    /** "BlogManager" (facade + main class) */
    public function mainClass(): string
    {
        return $this->nameStudly();
    }

    /** "blog-manager" (config file name + key) */
    public function configKey(): string
    {
        return Str::slug($this->name);
    }

    /** "blog_manager" (table / migration base) */
    public function tableName(): string
    {
        return Str::snake($this->name);
    }

    /** "Blog Manager" */
    public function title(): string
    {
        return Str::title($this->name);
    }

    /**
     * The replacement map applied to every stub.
     *
     * @return array<string, string>
     */
    public function replacements(): array
    {
        return [
            '{{ vendor }}'            => $this->vendor,
            '{{ vendor_studly }}'    => $this->vendorStudly(),
            '{{ name }}'             => $this->name,
            '{{ name_studly }}'      => $this->nameStudly(),
            '{{ composer_name }}'    => $this->composerName(),
            '{{ namespace }}'        => $this->namespace(),
            '{{ namespace_escaped }}' => $this->namespaceEscaped(),
            '{{ provider_class }}'   => $this->providerClass(),
            '{{ main_class }}'       => $this->mainClass(),
            '{{ facade_class }}'     => $this->mainClass(),
            '{{ config_key }}'       => $this->configKey(),
            '{{ table_name }}'       => $this->tableName(),
            '{{ title }}'            => $this->title(),
            '{{ description }}'      => $this->description,
            '{{ author_name }}'      => $this->authorName,
            '{{ author_email }}'     => $this->authorEmail,
            '{{ php_version }}'      => $this->phpVersion,
            '{{ type }}'             => $this->type,
            '{{ year }}'             => date('Y'),
        ];
    }
}
