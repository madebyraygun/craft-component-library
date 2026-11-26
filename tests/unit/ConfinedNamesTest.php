<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use Craft;
use madebyraygun\componentlibrary\controllers\BaseController;
use madebyraygun\componentlibrary\controllers\BrowserController;
use madebyraygun\componentlibrary\controllers\PreviewController;
use madebyraygun\componentlibrary\helpers\Common;
use madebyraygun\componentlibrary\helpers\Component;
use madebyraygun\componentlibrary\helpers\Context;
use madebyraygun\componentlibrary\helpers\Document;
use madebyraygun\componentlibrary\helpers\Library;
use madebyraygun\componentlibrary\helpers\Loader;
use madebyraygun\componentlibrary\Plugin;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Throwable;
use yii\web\NotFoundHttpException;

final class ConfinedNamesTest extends LibraryTestCase
{
    /**
     * Names that must never resolve. `{site}` and `{root}` are replaced with the fixture paths.
     *
     * @return array<string, array{string}>
     */
    public static function refusedNames(): array
    {
        return [
            'parent folder, template' => ['../templates/secret'],
            'parent folder, template with extension' => ['../templates/secret.twig'],
            'parent folder, markdown' => ['../notes.md'],
            'parent folder, markdown without extension' => ['../notes'],
            'parent folder, component-shaped folder' => ['../outside/evil'],
            'parent folder, env file' => ['../.env'],
            'parent folder behind an alias' => ['@elements/../../templates/secret'],
            'parent folder behind a real folder' => ['02-elements/button/../../../templates/secret'],
            'parent folder after a variant marker' => ['@elements/button--x/../../../templates/secret'],
            'many parent folders' => [str_repeat('../', 40) . 'etc/hosts'],
            'backslashes' => ['..\\templates\\secret'],
            'mixed separators' => ['..\\/templates/secret'],
            'dot segments' => ['./.././templates/secret'],
            'doubled slashes' => ['..//templates//secret'],
            'encoded dots, as text' => ['%2e%2e/templates/secret'],
            'double-encoded dots, as text' => ['%252e%252e/templates/secret'],
            'encoded slash, as text' => ['..%2ftemplates%2fsecret'],
            'overlong UTF-8 dots, as text' => ["\xC0\xAE\xC0\xAE/templates/secret"],
            'absolute path to a template' => ['{site}/templates/secret.twig'],
            'absolute path to markdown' => ['{site}/notes.md'],
            'absolute path through the root' => ['{root}/../templates/secret.twig'],
            'absolute system path' => ['/etc/hosts'],
            'stream wrapper' => ['php://filter/resource=../templates/secret.twig'],
            'file URL' => ['file://{site}/templates/secret.twig'],
            'null byte' => ["../templates/secret\0.twig"],
            'null byte after a real component' => ["@elements/button\0/../../../templates/secret"],
            'mixed case' => ['../Templates/SECRET'],
            'symlinked folder leaving the library' => ['link-out/evil'],
            'symlinked folder, aliased file' => ['link-out/evil/evil.twig'],
            'symlinked folder, markdown' => ['link-out/readme.md'],
            'symlinked template leaving the library' => ['@elements/leak'],
            'symlinked template, with extension' => ['02-elements/leak.twig'],
            'symlinked markdown leaving the docs folder' => ['@docs/leak'],
            'a directory named like a template' => ['folder.twig'],
            'a directory' => ['02-elements'],
            'the root itself' => ['.'],
            'the parent itself' => ['..'],
            'only an at sign' => ['@'],
            'very long name' => [str_repeat('a', 5000)],
            'very long traversal' => [str_repeat('../', 2000) . 'templates/secret'],
            'something that does not exist anywhere' => ['@elements/nothing-here'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allowedComponents(): array
    {
        return [
            'alias' => ['@elements/button'],
            'folder path' => ['02-elements/button'],
            'full file' => ['02-elements/button/button.twig'],
            'at sign and folder' => ['@02-elements/button'],
            'virtual variant' => ['@elements/button--outline'],
        ];
    }

    #[DataProvider('refusedNames')]
    public function testARefusedNameResolvesToNothing(string $name): void
    {
        $name = $this->fill($name);

        self::assertFalse(Loader::componentExists($name));
        self::assertFalse(Loader::documentExists($name));
        self::assertFalse(Loader::handleExists($name));
        self::assertSame('', Loader::handleType($name));
        self::assertNull(Plugin::$plugin->componentProvider->resolveHandlePath($name));
        self::assertNull(Plugin::$plugin->componentProvider->getDocumentContext($name));
        self::assertSame([], Plugin::$plugin->componentProvider->getComponentContext($name));
        self::assertNull(Plugin::$plugin->componentProvider->getComponentSettings($name));
        self::assertNull(Library::getUiToolbarContext($name));
    }

    #[DataProvider('refusedNames')]
    public function testARefusedNameCarriesNoPathAndReadsNoConfig(string $name): void
    {
        $name = $this->fill($name);
        $component = Component::parseComponentParts($name);
        $document = Document::parseDocumentParts($name);

        self::assertFalse($component->valid && Loader::componentExists($name));
        self::assertFalse($document->valid);
        foreach ([$component->templatePath, $component->configPath, $component->docPath, $document->docPath] as $path) {
            self::assertTrue($path === '' || str_starts_with($path, $this->root . '/'), 'left the library: ' . $path);
        }
        self::assertSame([], Context::readConfigFile($name));
        self::assertSame('', Library::getContextContents($name, false));
        self::assertStringNotContainsString('SECRET', Library::getDocContents($name));
        self::assertStringNotContainsString('SECRET', json_encode([$component, $document, Context::getComponentConfig($name)]) ?: '');
        self::assertFileDoesNotExist($this->marker, 'a config file outside the library was run');
    }

    #[DataProvider('refusedNames')]
    public function testEveryActionAnswers404ForARefusedName(string $name): void
    {
        Craft::$app->request->name = $this->fill($name);

        foreach ([[PreviewController::class, 'actionIndex'], [BrowserController::class, 'actionPartialToolbar'], [BrowserController::class, 'actionPartialPreview']] as [$class, $action]) {
            $thrown = $this->run404($class, $action);
            self::assertInstanceOf(NotFoundHttpException::class, $thrown, $class . '::' . $action);
        }
    }

    public function testThe404IsTheSameWhetherOrNotTheFileExists(): void
    {
        $answers = [];
        foreach (['../templates/secret', '../templates/no-such-file', '@elements/no-such-component', 'link-out/evil', "bad\0name"] as $name) {
            Craft::$app->request->name = $name;
            $thrown = $this->run404(PreviewController::class, 'actionIndex');
            self::assertInstanceOf(NotFoundHttpException::class, $thrown);
            $answers[] = [$thrown->getMessage(), $thrown->statusCode, $thrown->getCode()];
        }

        self::assertCount(1, array_unique($answers, SORT_REGULAR));
        self::assertSame(['', 404, 0], $answers[0]);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function namesThatAreNotStrings(): array
    {
        return [
            'missing' => [null],
            'empty' => [''],
            'array' => [['../templates/secret']],
            'nested array' => [['a' => ['b']]],
        ];
    }

    #[DataProvider('namesThatAreNotStrings')]
    public function testThePreviewAnswers404WithoutAUsableName(mixed $name): void
    {
        Craft::$app->request->name = $name;

        self::assertInstanceOf(NotFoundHttpException::class, $this->run404(PreviewController::class, 'actionIndex'));
    }

    #[DataProvider('allowedComponents')]
    public function testComponentsInsideTheLibraryStillResolve(string $name): void
    {
        $name = $this->fill($name);

        self::assertTrue(Loader::componentExists($name));
        self::assertSame($this->root . '/02-elements/button/button.twig', Plugin::$plugin->componentProvider->resolveHandlePath($name));
        self::assertNotNull(Library::getUiToolbarContext($name));
    }

    public function testConfigAndDocsInsideTheLibraryAreStillRead(): void
    {
        self::assertSame(['label' => 'Go'], Plugin::$plugin->componentProvider->getComponentContext('@elements/button'));
        self::assertSame(['label' => 'Out'], Plugin::$plugin->componentProvider->getComponentContext('@elements/button--outline'));
        self::assertTrue(Loader::documentExists('@docs/index'));
        self::assertSame('# Welcome', Plugin::$plugin->componentProvider->getDocumentContext('@docs/index')['content']);
    }

    public function testAConfigFileThatIsASymlinkOutOfTheLibraryIsNotRun(): void
    {
        self::assertTrue(Loader::componentExists('@elements/card'));
        self::assertSame(['title' => 'Card'], Plugin::$plugin->componentProvider->getComponentContext('@elements/card'));
        self::assertFileDoesNotExist($this->marker);
    }

    public function testADocsFolderNextToTheLibraryIsAllowedAndNothingElseIs(): void
    {
        $this->write('docs/guide.md', '# Guide');
        $this->useLibrary(['docs' => $this->site . '/docs']);

        self::assertTrue(Common::isFileInsideLibrary($this->site . '/docs/guide.md'));
        self::assertTrue(Loader::documentExists('../docs/guide'));
        self::assertFalse(Loader::documentExists('../notes'));
        self::assertFalse(Loader::documentExists('../docs/../notes'));
        self::assertFalse(Common::isInsideLibrary($this->site . '/docs-private/x.md'));
    }

    public function testAFolderWhoseNameStartsLikeTheRootIsOutside(): void
    {
        $this->write('library-private/secret.twig', 'SECRET');

        self::assertFalse(Common::isInsideLibrary($this->root . '-private/secret.twig'));
        self::assertFalse(Common::isFileInsideLibrary($this->root . '-private/secret.twig'));
        self::assertFalse(Loader::componentExists('../library-private/secret'));
        self::assertFalse(Common::isInsideLibrary($this->root));
    }

    public function testALibraryReachedThroughASymlinkStillWorks(): void
    {
        symlink($this->root, $this->site . '/library-link');
        $this->useLibrary(['root' => $this->site . '/library-link', 'docs' => $this->site . '/library-link/docs']);

        self::assertTrue(Loader::componentExists('@elements/button'));
        self::assertFalse(Loader::componentExists('../templates/secret'));
        self::assertFalse(Loader::componentExists('@elements/leak'));
    }

    public function testTheTreeListsOnlyFilesInsideTheLibrary(): void
    {
        $index = Library::getSearchIndexFromTrees([Library::getLibraryTree(), Library::getDocumentsTree()]);
        $handles = array_column($index, 'includeName');

        self::assertContains('@02-elements/button', $handles);
        self::assertContains('@02-elements/button--outline', $handles);
        self::assertContains('@02-elements/card', $handles);
        self::assertContains('@docs/index', $handles);
        foreach ($handles as $handle) {
            self::assertStringNotContainsString('leak', $handle);
            self::assertStringNotContainsString('evil', $handle);
        }
    }

    public function testNoAbsolutePathIsShown(): void
    {
        $trees = [Library::getLibraryTree(), Library::getDocumentsTree()];
        $shown = json_encode([
            Library::getSearchIndexFromTrees($trees),
            array_map(static fn(array $node): array => array_diff_key($node, ['nodes' => 1]), $trees[0]['nodes']),
            Library::getUiToolbarContext('@elements/button')['file'],
            Library::getUiToolbarContext('@elements/button')['contents'],
        ], JSON_UNESCAPED_SLASHES);

        self::assertIsString($shown);
        self::assertStringNotContainsString($this->site, $shown);
        self::assertStringNotContainsString(sys_get_temp_dir(), $shown);
        self::assertSame('02-elements/button/button.twig', Library::getUiToolbarContext('@elements/button')['file']);
        self::assertStringNotContainsString('templatePath', (string)file_get_contents(dirname(__DIR__, 2) . '/src/templates/_partials/toolbar_info.twig'));
    }

    public function testARequestNameAlwaysBecomesALibraryHandle(): void
    {
        self::assertSame('@elements/button', Common::libraryHandle('@elements/button'));
        self::assertSame('@02-elements/button', Common::libraryHandle('02-elements/button'));
        self::assertSame('@index', Common::libraryHandle('index'));
    }

    /**
     * @param class-string<BaseController> $class
     */
    private function run404(string $class, string $action): ?Throwable
    {
        $controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        $controller->request = Craft::$app->request;
        try {
            $controller->$action();
        } catch (Throwable $e) {
            return $e;
        }

        return null;
    }

    private function fill(string $name): string
    {
        return str_replace(['{site}', '{root}'], [$this->site, $this->root], $name);
    }
}
