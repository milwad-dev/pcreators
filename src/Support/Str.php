<?php

declare(strict_types=1);

namespace Pcreators\Support;

final class Str
{
    /**
     * "blog-manager" / "blog_manager" / "Blog Manager" -> "BlogManager".
     */
    public static function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);

        return str_replace(' ', '', $value);
    }

    /**
     * "BlogManager" / "Blog Manager" -> "blog-manager".
     */
    public static function kebab(string $value): string
    {
        return self::slug($value);
    }

    /**
     * "BlogManager" / "blog manager" -> "blog_manager".
     */
    public static function snake(string $value): string
    {
        return str_replace('-', '_', self::slug($value));
    }

    /**
     * "BlogManager Pro!" -> "blog-manager-pro".
     */
    public static function slug(string $value): string
    {
        // Split camelCase / PascalCase boundaries first.
        $value = (string) preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', ' ', $value);
        $value = strtolower($value);
        $value = (string) preg_replace('/[^a-z0-9]+/', '-', $value);

        return trim($value, '-');
    }

    public static function title(string $value): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $value));
    }
}
