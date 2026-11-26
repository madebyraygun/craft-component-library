<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use craft\web\View;
use ReflectionClass;
use RuntimeException;

/**
 * A view that knows no site templates, and whose rendering fails with a message that names a path.
 */
final class StubView extends View
{
    /** @var string[] */
    public array $resolved = [];

    public string $failure = '';

    public static function create(): self
    {
        return (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
    }

    public function resolveTemplate(string $name, ?string $templateMode = null, bool $publicOnly = false): string|false
    {
        $this->resolved[] = $name;

        return false;
    }

    public function doesTemplateExist(string $name, ?string $templateMode = null, bool $publicOnly = false): bool
    {
        return false;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function renderTemplate(string $template, array $variables = [], ?string $templateMode = null): string
    {
        throw new RuntimeException($this->failure);
    }
}
