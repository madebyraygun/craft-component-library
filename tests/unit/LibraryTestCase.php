<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use Craft;
use craft\helpers\FileHelper;
use madebyraygun\componentlibrary\helpers\Component;
use madebyraygun\componentlibrary\helpers\Context;
use madebyraygun\componentlibrary\helpers\Document;
use madebyraygun\componentlibrary\Plugin;
use madebyraygun\componentlibrary\services\ComponentProvider;
use ReflectionProperty;
use Throwable;

/**
 * A small library on disk, inside a `site` folder that also holds files the library must not serve.
 * Every file outside the library contains the word OUTSIDE.
 */
abstract class LibraryTestCase extends PluginTestCase
{
    public const OUTSIDE = 'OUTSIDE';

    protected string $site = '';

    protected string $root = '';

    /** A file that the PHP fixtures outside a component's own config create when they are run. */
    protected string $marker = '';

    protected function setUp(): void
    {
        parent::setUp();
        // Common::collapseHandlePath() lower-cases handles, so the fixture path must be lower-case.
        $base = is_writable('/tmp') ? '/tmp' : sys_get_temp_dir();
        $this->site = (string)realpath($base) . '/component-library-test-' . bin2hex(random_bytes(6));
        $this->root = $this->site . '/library';
        $this->marker = $this->site . '/php-file-was-run';

        try {
            $this->buildLibrary();
            $this->buildOutsideFiles();
            $this->useLibrary();
        } catch (Throwable $e) {
            $this->tearDown();
            throw $e;
        }
    }

    protected function tearDown(): void
    {
        if ($this->site !== '' && is_dir($this->site)) {
            FileHelper::removeDirectory($this->site);
        }
        Plugin::$plugin = null;
        Craft::setAlias('@root', sys_get_temp_dir());
        parent::tearDown();
    }

    private function buildLibrary(): void
    {
        $this->write('library/02-elements/button/button.twig', '<a class="button">{{ label }}</a>');
        $this->write('library/02-elements/button/button--close.twig', '<a class="button button--close"></a>');
        $this->write('library/02-elements/button/button.config.php', "<?php\nreturn ['context' => ['label' => 'Go'], 'variants' => [['name' => 'Outline', 'context' => ['label' => 'Out']]]];\n");
        $this->write('library/02-elements/button/readme.md', '# Button');
        $this->write('library/02-elements/card/card.twig', '<div class="card"></div>');
        $this->write('library/02-elements/card/card.config.json', '{"context": {"title": "Card"}}');
        $this->write('library/02-elements/form/inputs/checkbox/checkbox.twig', '<input type="checkbox">');
        $this->write('library/02-elements/Banner/banner.twig', '<div class="banner"></div>');
        $this->write('library/02-elements/card.v2/card.v2.twig', '<div class="card card--v2"></div>');
        $this->write('library/docs/index.md', '# Welcome');
        $this->write('library/docs/guide/setup.md', '# Setup');
        $this->write('library/folder.twig/keep.txt', 'a directory with a template name');
        $this->write('library/plain.php', $this->phpThatLeavesTheMarker());
        $this->write('library/sub/other.php', $this->phpThatLeavesTheMarker());
        $this->write('library/sub/notes.txt', 'plain text in the library');
    }

    private function buildOutsideFiles(): void
    {
        $this->write('templates/other.twig', self::OUTSIDE . ' template');
        $this->write('templates/other.config.php', $this->phpThatLeavesTheMarker());
        $this->write('notes.md', self::OUTSIDE . ' notes');
        $this->write('settings.txt', self::OUTSIDE . ' settings');
        $this->write('other/component/component.twig', self::OUTSIDE . ' component');
        $this->write('other/component/component.config.php', $this->phpThatLeavesTheMarker());
        $this->write('other/readme.md', self::OUTSIDE . ' readme');
    }

    /**
     * Links inside the library whose targets are outside it, and one that leads back to the root.
     */
    protected function addLinks(): void
    {
        $links = [
            $this->root . '/linked-folder' => $this->site . '/other',
            $this->root . '/02-elements/linked.twig' => $this->site . '/templates/other.twig',
            $this->root . '/docs/linked.md' => $this->site . '/notes.md',
            $this->root . '/02-elements/card/card.config.php' => $this->site . '/other/component/component.config.php',
            $this->root . '/02-elements/loop' => $this->root,
        ];
        foreach ($links as $link => $target) {
            if (!function_exists('symlink') || !@symlink($target, $link)) {
                self::markTestSkipped('Symlinks are not available here.');
            }
        }
        $this->useLibrary();
    }

    /**
     * @param array<string, mixed> $settings
     */
    protected function useLibrary(array $settings = []): Plugin
    {
        foreach ([Component::class, Document::class, Context::class] as $class) {
            (new ReflectionProperty($class, 'cache'))->setValue(null, []);
        }
        // An absolute browser path lets Craft build the browser's URLs without a running site.
        $plugin = $this->pluginWith(['enabled' => true, 'requiresLogin' => false, 'path' => 'https://library.test/component-library']);
        $plugin->setSettings($settings + [
            'root' => $this->root,
            'docs' => $this->root . '/docs',
            'aliases' => ['@elements' => '02-elements', '@docs' => 'docs'],
        ]);
        $plugin->set('componentProvider', new ComponentProvider());
        Plugin::$plugin = $plugin;
        Craft::setAlias('@root', $this->site);
        Craft::$app->request = new class {
            public mixed $name = null;

            public function getParam(string $key): mixed
            {
                return $key === 'name' ? $this->name : null;
            }
        };
        Craft::$app->response = new class {
            public int $statusCode = 200;

            public function setStatusCode(int $code): void
            {
                $this->statusCode = $code;
            }
        };

        return $plugin;
    }

    protected function write(string $path, string $contents): void
    {
        $file = $this->site . '/' . $path;
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, $contents);
    }

    private function phpThatLeavesTheMarker(): string
    {
        return "<?php\nfile_put_contents(" . var_export($this->marker, true) . ", 'x');\nreturn ['context' => ['text' => '" . self::OUTSIDE . " config']];\n";
    }
}
