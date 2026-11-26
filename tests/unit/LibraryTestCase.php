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

/**
 * A small library on disk, with files next to it that must stay out of reach:
 *
 *   site/library/                     the library root
 *   site/library/docs/                the docs folder
 *   site/templates/secret.twig        a site template
 *   site/notes.md                     a markdown file
 *   site/outside/evil/evil.twig       a component-shaped folder with a config that leaves a marker when it runs
 *   site/library/link-out          -> site/outside
 *   site/library/02-elements/leak.twig -> site/templates/secret.twig
 */
abstract class LibraryTestCase extends PluginTestCase
{
    protected string $site = '';

    protected string $root = '';

    protected string $marker = '';

    protected function setUp(): void
    {
        parent::setUp();
        // Handles are lower-cased before they are resolved, so the fixture needs a lower-case path.
        $base = is_writable('/tmp') ? '/tmp' : sys_get_temp_dir();
        $this->site = (string)realpath($base) . '/component-library-test-' . bin2hex(random_bytes(6));
        $this->root = $this->site . '/library';
        $this->marker = $this->site . '/config-was-run';

        $this->write('library/02-elements/button/button.twig', '<a class="button">{{ label }}</a>');
        $this->write('library/02-elements/button/button.config.php', "<?php\nreturn ['context' => ['label' => 'Go'], 'variants' => [['name' => 'Outline', 'context' => ['label' => 'Out']]]];\n");
        $this->write('library/02-elements/button/button.md', '# Button');
        $this->write('library/02-elements/card/card.twig', '<div class="card"></div>');
        $this->write('library/02-elements/card/card.config.json', '{"context": {"title": "Card"}}');
        $this->write('library/docs/index.md', '# Welcome');
        $this->write('library/folder.twig/keep.txt', 'a directory with a template name');
        $this->write('templates/secret.twig', 'SECRET TEMPLATE SOURCE');
        $this->write('templates/secret.config.php', "<?php\nfile_put_contents(" . var_export($this->marker, true) . ", 'x');\nreturn ['context' => ['leak' => 'SECRET CONFIG']];\n");
        $this->write('notes.md', 'SECRET NOTES');
        $this->write('.env', 'SECRET_KEY=abc');
        $this->write('outside/evil/evil.twig', 'SECRET OUTSIDE COMPONENT');
        $this->write('outside/evil/evil.config.php', "<?php\nfile_put_contents(" . var_export($this->marker, true) . ", 'x');\nreturn ['context' => ['leak' => 'SECRET CONFIG']];\n");
        $this->write('outside/readme.md', 'SECRET OUTSIDE DOC');
        symlink($this->site . '/outside', $this->root . '/link-out');
        symlink($this->site . '/templates/secret.twig', $this->root . '/02-elements/leak.twig');
        symlink($this->site . '/notes.md', $this->root . '/docs/leak.md');
        symlink($this->site . '/outside/evil/evil.config.php', $this->root . '/02-elements/card/card.config.php');

        $this->useLibrary();
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->site);
        Plugin::$plugin = null;
        parent::tearDown();
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
        Craft::$app->request = new class {
            public mixed $name = null;

            public function getParam(string $key): mixed
            {
                return $key === 'name' ? $this->name : null;
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
}
